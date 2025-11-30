<?php

test('homepage is accessible', function () {
    $this->get('/')
        ->assertStatus(200);
});
