<?php
/**
 * Created by PhpStorm.
 * User: Thodin
 * Date: 01.02.2021
 * Time: 16:39
 */

namespace Thodin\ShopaholicProductCopyist\Controllers;

use Backend\Classes\Controller;
use Lovata\Shopaholic\Models\Category;

/**
 * Class Products
 * @package Thodin\ShopaholicProductCopyist\Controllers
 */
class Products extends Controller
{
    /**
     * @var array
     */
    public $implement = [
        'Backend.Behaviors.FormController',
    ];

    /**
     * @var string
     */
    public $formConfig = 'config_form.yaml';

    /**
     * Products constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->addJs('/modules/backend/assets/js/october.treeview.js', 'core');
        $this->addJs('/plugins/thodin/shopaholicproductcopyist/assets/js/copyist.js');
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $this->bodyClass = 'compact-container';

        $this->addViewPath(__DIR__ . DIRECTORY_SEPARATOR . '../partials');

        $arResult = [];
        $obCategoryList = Category::active()->orderBy('nest_left', 'asc')->get();

        /** @var Category $obCategory */
        foreach ($obCategoryList as $obCategory)
        {
            $arResult[$obCategory->id] = $obCategory->name;
        }

        return $this->makePartial('select_category', ['categories' => $arResult]);
    }

    /**
     * @return array
     */
    public function onGetCategoryList()
    {
        $arResult = [];
        $sQuery = post('q');

        if (is_null($sQuery) || mb_strlen($sQuery) < 2)
        {
            return ['result' => $arResult];
        }

        //Get category list with sorted by 'nest_left'
        $obCategoryList = Category::active()->orderBy('nest_left', 'asc')->get();
        if ($obCategoryList->isEmpty())
        {
            return $arResult;
        }

        /** @var Category $obCategory */
        foreach ($obCategoryList as $obCategory)
        {
            $bSearchResult = mb_strpos(mb_strtolower($obCategory->name), mb_strtolower($sQuery));

            if ($bSearchResult !== false)
            {
                $arResult[$obCategory->id] = $obCategory->name
                    . ($obCategory->parent ? (' [' . $obCategory->parent->name . ']') : '');
            }
        }


        return ['result' => $arResult];
    }
}
