<?php

namespace EdgarIndustries\RedirectMiddleware\Model;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

class Redirect extends DataObject implements Flushable
{
    private static $table_name = 'EdgarIndustries_RedirectMiddleware_Redirect';

    private static $singular_name = 'Redirect';
    private static $plural_name = 'Redirects';

    private static $default_sort = 'Active DESC, FromURL ASC';

    private static $summary_fields = [
        'FromURL' => 'From',
        'DestinationSummary' => 'To',
        'ActiveNice' => 'Enabled',
    ];

    private static $db = [
        'FromURL' => 'Varchar(255)',
        'ToURL' => 'Text',
        'Active' => 'Boolean',
        'Code' => 'Enum("301,302", "302")',
    ];

    private static $has_one = [
        'ToPage' => SiteTree::class,
    ];

    private static $defaults = [
        'Active' => true,
    ];

    public static function flush()
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.redirectMiddlewareCache');
        $cache->clear();

        $redirects = Redirect::get()->filter('Active', true);
        if ($redirects->exists()) {
            foreach ($redirects as $r) {
                $cache->set(str_replace('/', '^', strtolower($r->FromURL)), $r->getDestination());
            }
        }
    }

    public function getActiveNice()
    {
        return $this->Active ? '✓' : '✘';
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->changeFieldOrder(['Active', 'FromURL', 'Code', 'ToPageID', 'ToURL']);

        $fields->dataFieldByName('Active')->setTitle('Enabled');
        $fields->replaceField('Code', OptionsetField::create(
            'Code',
            'Type',
            [
                '302' => 'Temporary',
                '301' => 'Permanent',
            ],
            $this->Code
        ));
        $fields->dataFieldByName('FromURL')
            ->setTitle('From (path)')
            ->setDescription('e.g. <code>oldsection/page-name</code> (case insensitive)');
        $fields->dataFieldByName('ToPageID')->setTitle('To (internal page)');
        $fields->replaceField(
            'ToURL',
            TextField::create('ToURL', 'To (external URL)')
                ->setDescription('e.g. <code>https://www.silverstripe.org</code>')
        );

        $fields->insertAfter('ToURL', LiteralField::create(
            'RedirectNote',
            '<strong>Note:</strong> If both "internal page" and "external URL" are set, the internal page will be used.')
        );
        return $fields;
    }

    public function getDestination()
    {
        if ($this->ToPageID > 0) {
            return $this->ToPage()->AbsoluteLink();
        } else {
            return $this->ToURL;
        }
    }

    public function getDestinationSummary()
    {
        if ($this->ToPageID > 0) {
            return $this->ToPage()->Breadcrumbs();
        } else {
            return $this->ToURL;
        }
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();

        self::flush();
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        self::flush();
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Strip extra slashes
        if (strpos($this->FromURL, '/') === 0) {
            $this->FromURL = substr($this->FromURL, 1);
        }
        if (strpos($this->FromURL, '/') === (strlen($this->FromURL) - 1)) {
            $this->FromURL = substr($this->FromURL, 0, strlen($this->FromURL) - 1);
        }

        // Rewrite blank (i.e. homepage) to 'home'
        if ($this->FromURL == '') {
            $this->FromURL = 'home';
        }
    }
}
