<?php

test('the root url redirects to the admin panel', function () {
    $this->get('/')->assertRedirect('/admin');
});
