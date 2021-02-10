<?php namespace Thodin\ShopaholicProductCopyist;

use Backend\Classes\Controller;
use Event;
use Lovata\Shopaholic\Controllers\Products;
use Lovata\Shopaholic\Models\Category;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    /** @var array Plugin dependencies */
    public $require = ['Lovata.Shopaholic'];

    /**
     * @return array|void
     */
    public function registerComponents()
    {
    }

    /**
     * @return array|void
     */
    public function registerSettings()
    {
    }

    /**
     *
     */
    public function boot()
    {
        // { #< ?= $this->makePartial('select_category') ?  ># }
        //lovata.view.extendProductsToolbar
        Event::listen('lovata.backend.extend_list_toolbar', function (Controller $controller) {
            if ($controller instanceof Products)
            {
                $arResult = [];
                $obCategoryList = Category::active()->orderBy('nest_left', 'asc')->get();

                /** @var Category $obCategory */
                foreach ($obCategoryList as $obCategory)
                {
                    $arResult[$obCategory->id] = $obCategory->name;
                }

                return $controller->makePartial('$/thodin/shopaholicproductcopyist/partials/_copy_button.htm',
                    ['categories' => $arResult]);
            }

            return null;
        });

        Products::extend(function ($controller) {
            $controller->implement[] = 'Thodin\ShopaholicProductCopyist\Behaviors\CopyProductBehavior';
        });
    }
}
