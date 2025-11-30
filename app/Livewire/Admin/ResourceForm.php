<?php

namespace App\Livewire\Admin;

use App\Enums\PricingModel;
use App\Enums\PricingUnit;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Models\Resource;
use App\Models\ResourceImage;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Admin component for creating and editing resources.
 *
 * Handles resource details, pricing configuration, and image uploads.
 */
#[Layout('components.layouts.admin')]
class ResourceForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?Resource $resource = null;

    // Basic info
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:2000')]
    public string $description = '';

    #[Validate('required|string')]
    public string $type = '';

    #[Validate('required|string')]
    public string $status = '';

    // Pricing
    #[Validate('required|string')]
    public string $pricing_model = '';

    #[Validate('required|numeric|min:0')]
    public string $rate = '0.00';

    #[Validate('nullable|string')]
    public ?string $pricing_unit = null;

    // Availability
    #[Validate('boolean')]
    public bool $requires_approval = false;

    /**
     * Max reservation days: 0 = single day only, positive = max days, null = unlimited.
     */
    #[Validate('nullable|integer|min:0|max:365')]
    public ?int $max_reservation_days = null;

    #[Validate('nullable|integer|min:1|max:365')]
    public ?int $advance_booking_days = null;

    // Images
    #[Validate(['images.*' => 'image|max:2048'])]
    public array $images = [];

    public array $existingImages = [];

    public array $imagesToDelete = [];

    public bool $isEdit = false;

    /**
     * Mount the component.
     */
    public function mount(?Resource $resource = null): void
    {
        if ($resource && $resource->exists) {
            $this->resource = $resource;
            $this->isEdit = true;
            $this->loadResourceData();
        } else {
            $this->setDefaults();
        }
    }

    /**
     * Set default values for new resources.
     */
    private function setDefaults(): void
    {
        $this->type = ResourceType::Vehicle->value;
        $this->status = ResourceStatus::Active->value;
        $this->pricing_model = PricingModel::FlatFee->value;
        $this->rate = '0.00';
    }

    /**
     * Load resource data into form fields.
     */
    private function loadResourceData(): void
    {
        $this->name = $this->resource->name;
        $this->description = $this->resource->description ?? '';
        $this->type = $this->resource->type->value;
        $this->status = $this->resource->status->value;
        $this->pricing_model = $this->resource->pricing_model->value;
        $this->rate = (string) $this->resource->rate;
        $this->pricing_unit = $this->resource->pricing_unit?->value;
        $this->requires_approval = $this->resource->requires_approval;
        $this->max_reservation_days = $this->resource->max_reservation_days;
        $this->advance_booking_days = $this->resource->advance_booking_days;

        // Load existing images
        $this->existingImages = $this->resource->images->map(fn ($img) => [
            'id' => $img->id,
            'url' => $img->url,
            'filename' => $img->filename,
            'is_primary' => $img->is_primary,
        ])->toArray();
    }

    /**
     * Handle pricing model change to show/hide unit selection.
     */
    public function updatedPricingModel(): void
    {
        if ($this->pricing_model === PricingModel::FlatFee->value) {
            $this->pricing_unit = null;
        } elseif (! $this->pricing_unit) {
            $this->pricing_unit = PricingUnit::Hour->value;
        }
    }

    /**
     * Remove an image from the upload queue.
     */
    public function removeImage(int $index): void
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    /**
     * Mark an existing image for deletion.
     */
    public function markImageForDeletion(int $imageId): void
    {
        $this->imagesToDelete[] = $imageId;
        $this->existingImages = array_filter(
            $this->existingImages,
            fn ($img) => $img['id'] !== $imageId
        );
    }

    /**
     * Set an image as primary.
     */
    public function setPrimaryImage(int $imageId): void
    {
        foreach ($this->existingImages as &$img) {
            $img['is_primary'] = $img['id'] === $imageId;
        }
    }

    /**
     * Save the resource.
     */
    public function save(): void
    {
        $this->validate();

        // Validate pricing unit when per-unit is selected
        if ($this->pricing_model === PricingModel::PerUnit->value && ! $this->pricing_unit) {
            $this->addError('pricing_unit', 'Please select a unit for per-unit pricing.');

            return;
        }

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'status' => $this->status,
            'pricing_model' => $this->pricing_model,
            'rate' => (float) $this->rate,
            'pricing_unit' => $this->pricing_model === PricingModel::PerUnit->value
                ? $this->pricing_unit
                : null,
            'requires_approval' => $this->requires_approval,
            // max_reservation_days: 0 = single day only, positive = max multi-day, null = unlimited
            'max_reservation_days' => $this->max_reservation_days,
            'advance_booking_days' => $this->advance_booking_days ?: null,
        ];

        if ($this->isEdit) {
            $this->resource->update($data);
            $resource = $this->resource;
        } else {
            $data['created_by'] = auth()->id();
            $resource = Resource::create($data);
        }

        // Handle image deletions
        foreach ($this->imagesToDelete as $imageId) {
            $image = ResourceImage::find($imageId);
            $image?->deleteWithFile();
        }

        // Handle new image uploads
        $this->uploadImages($resource);

        // Update primary image flags
        $this->updatePrimaryFlags($resource);

        $message = $this->isEdit
            ? "{$resource->name} has been updated."
            : "{$resource->name} has been created.";

        session()->flash('success', $message);

        $this->redirect(route('admin.resources'), navigate: true);
    }

    /**
     * Upload new images.
     */
    private function uploadImages(Resource $resource): void
    {
        $order = $resource->images()->max('order') ?? 0;

        foreach ($this->images as $image) {
            $path = $image->store('resource-images', 'public');
            $filename = $image->getClientOriginalName();

            $resource->images()->create([
                'path' => $path,
                'filename' => $filename,
                'order' => ++$order,
                'is_primary' => $resource->images()->count() === 0,
            ]);
        }
    }

    /**
     * Update primary image flags for existing images.
     */
    private function updatePrimaryFlags(Resource $resource): void
    {
        foreach ($this->existingImages as $img) {
            ResourceImage::where('id', $img['id'])->update([
                'is_primary' => $img['is_primary'],
            ]);
        }
    }

    public function render()
    {
        $title = $this->isEdit ? "Edit {$this->resource->name}" : 'Create Resource';

        return view('livewire.admin.resource-form', [
            'types' => ResourceType::cases(),
            'statuses' => ResourceStatus::cases(),
            'pricingModels' => PricingModel::cases(),
            'pricingUnits' => PricingUnit::cases(),
        ])->title("{$title} - TribeTrip Admin");
    }
}
