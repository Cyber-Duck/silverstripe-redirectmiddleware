<?php

namespace EdgarIndustries\RedirectMiddleware\Admin;

use EdgarIndustries\RedirectMiddleware\Model\Redirect;
use SilverStripe\Admin\ModelAdmin;

class RedirectAdmin extends ModelAdmin
{
    private static $managed_models = [
        Redirect::class
    ];

    private static $menu_title = 'Redirects';
    private static $url_segment = 'redirectmiddleware';
}
