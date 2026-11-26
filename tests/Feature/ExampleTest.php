<?php

test('root redirects to the admin login', function () {
    $this->get('/')->assertRedirect('/admin/login');
});
