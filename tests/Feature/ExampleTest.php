<?php

test('returns a successful response', function () {
    $response = $this->get(route('blog.index'));

    $response->assertOk();
});
