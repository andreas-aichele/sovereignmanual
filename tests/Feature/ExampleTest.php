<?php

test('returns a successful response', function () {
    $response = $this->get(route('magazine.index'));

    $response->assertOk();
});
