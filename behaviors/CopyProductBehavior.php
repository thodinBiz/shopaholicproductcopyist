<?php namespace Thodin\ShopaholicProductCopyist\Behaviors;

use Backend\Classes\ControllerBehavior;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Lovata\PropertiesShopaholic\Models\PropertyValueLink;
use Lovata\Shopaholic\Models\Category;
use Lovata\Shopaholic\Models\Offer;
use Lovata\Shopaholic\Models\Product;
use System\Classes\PluginManager;
use System\Models\File;

/**
 * Class CopyProductBehavior
 * @package Thodin\ShopaholicProductCopyist\Behaviors
 */
class CopyProductBehavior extends ControllerBehavior
{

    /** @var PluginManager */
    protected static $obPluginManager;

    /**
     * @return mixed
     */
    public function onCopyProducts()
    {
        $productIds = post('checked') ?: [];
        Product::whereIn('id', $productIds)->get()->each(function (Product $product) {
            $clone = self::cloneProduct($product);
            $clone->save();
        });

        return $this->controller->listRefresh();
    }

    /**
     * @param Product $obProduct
     *
     * @return Product
     */
    private static function cloneProduct(Product $obProduct)
    {
        $iCategoryId = post('categoryId', $obProduct->category_id);

        self::$obPluginManager = PluginManager::instance();

        /** @var Category $obTargetCategory */
        $obTargetCategory = Category::whereId($iCategoryId)->firstOrFail();

        $data = self::buildNewProductData($obProduct, $obTargetCategory);

        $obProductCopy = Product::create($data);

        $obProduct->additional_category->each(function ($category) use ($obProductCopy) {
            $obProductCopy->additional_category()->attach($category);
        });

        $obProductCopy->preview_image = self::getImagePath($obProduct->preview_image);
        self::copyImages($obProduct, $obProductCopy);

        $obProduct->offer->each(function ($offer) use ($obProductCopy) {
            self::createOffer($obProductCopy, $offer);
        });

        if (self::$obPluginManager->exists('lovata.propertiesshopaholic'))
        {
            self::copyProperties($obProduct, $obProductCopy);
        }

        return $obProductCopy;
    }

    /**
     * @param Product  $obProduct
     * @param Category $obTargetCategory
     *
     * @return array
     */
    protected static function buildNewProductData(Product $obProduct, Category $obTargetCategory)
    {
        $uniqueHash = substr(md5(uniqid(mt_rand(), true)), 0, 4);
        $slug = $obProduct->slug . '-' . $uniqueHash;

        $data = [
            'slug'         => $slug,
            'name'         => $obProduct->name,
            'preview_text' => $obProduct->preview_text,
            'description'  => $obProduct->description,
            'active'       => $obProduct->active,
            'code'         => $obProduct->code,
            'brand_id'     => $obProduct->brand_id,
            'category_id'  => $obTargetCategory->id,
            'external_id'  => $obProduct->external_id,
        ];

        if (self::$obPluginManager->exists('lovata.popularityshopaholic'))
        {
            $data['popularity'] = $obProduct->popularity;
        }

        return $data;
    }

    /**
     * @param $imagesOwner
     * @param $imagesAssignee
     */
    private static function copyImages($imagesOwner, $imagesAssignee)
    {
        $imagesOwner->images->each(function ($image) use ($imagesAssignee) {
            $file = new File();
            try
            {
                $file = $file->fromFile(self::getImagePath($image));
                $file->file_name = $image->getFilename();
                $file->title = $image->title;
                $file->description = $image->description;
                $imagesAssignee->images()->add($file);
            } catch (Exception $exception)
            {
            }
        });
    }

    /**
     * @param File|null $image
     *
     * @return string
     */
    private static function getImagePath(?File $image)
    {
        if ($image == null)
        {
            return '';
        }

        return storage_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $image->getDiskPath();
    }

    /**
     * @param Product $product
     * @param Offer   $offer
     */
    private static function createOffer(Product $product, Offer $offer)
    {
        $copiedOffer = Offer::create([
            'product_id'       => $product->id,
            'active'           => $offer->active,
            'name'             => $offer->name,
            'code'             => $offer->code,
            'price'            => $offer->price,
            'weight'           => $offer->weight,
            'quantity'         => $offer->quantity,
            'measure'          => $offer->measure,
            'height'           => $offer->height,
            'length'           => $offer->length,
            'width'            => $offer->width,
            'quantity_in_unit' => $offer->quantity_in_unit,
            'measure_of_unit'  => $offer->measure_of_unit,
            'preview_text'     => $offer->preview_text,
            'description'      => $offer->description,
        ]);
        $copiedOffer->preview_image = self::getImagePath($offer->preview_image);
        self::copyImages($offer, $copiedOffer);
        $copiedOffer->save();
    }

    /**
     * Copy properties of a product
     *
     * @param Product $obProduct
     * @param Product $obProductCopy
     *
     * @return Collection
     */
    private static function copyProperties(Product $obProduct, Product $obProductCopy)
    {
        $obCollectionPropertiesValueLink = PropertyValueLink::whereProductId($obProduct->id)->get();

        /** @var PropertyValueLink $obPropertyValueLink */
        foreach ($obCollectionPropertiesValueLink as $obPropertyValueLink)
        {
            $data = [
                'value_id'     => $obPropertyValueLink->value_id,
                'property_id'  => $obPropertyValueLink->property_id,
                'product_id'   => $obProductCopy->id,
                'element_id'   => $obProductCopy->id,
                'element_type' => $obPropertyValueLink->element_type,

            ];

            PropertyValueLink::create($data);
        }

        return $obCollectionPropertiesValueLink;
    }
}
