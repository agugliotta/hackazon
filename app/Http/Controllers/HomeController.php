<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\SpecialOffer as SpecialOffers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class HomeController
 * @package App\Http\Controllers
 * @Vuln\Description(description="This is a homepage controller.")
 */
class HomeController extends PageController
{
    const COUNT_RND_PRODUCTS = 3;

    protected int $topViewedCount       = 4;
    protected int $relatedToVisitedCount = 8;
    protected int $bestChoiceCount       = 4;
    protected int $reviewsCount          = 2;

    /**
     * @Vuln\Description("View used: home/home")
     */
    public function index(Request $request)
    {
        $mostPopularProductsCount  = 3;
        $bestSellingProductsCount  = 3;
        $specialOffersCount        = 3;
        $otherCustomerProductCount = 4;
        $randomProductsCount       = 4;
        $productSectionReviewCount = 3;

        // Cookie with visited product IDs (unvalidated — IDOR preserved)
        $visitedProductIds = $request->cookie('visited_products');

        $rndProducts              = Product::getRndProduct(self::COUNT_RND_PRODUCTS);
        $relatedToVisitedProducts = Product::getVisitedProducts($visitedProductIds);
        $bestChoiceProducts       = Product::getRandomProducts($this->bestChoiceCount);
        $mostPopularProducts      = Product::getRandomProducts($mostPopularProductsCount);
        $bestSellingProducts      = Product::getRandomProducts($bestSellingProductsCount);
        $randomProducts           = Product::getRandomProducts($randomProductsCount);
        $specialOffers            = SpecialOffers::getRandomOffers($specialOffersCount);
        $selectedReviews          = Review::getRandomReviews($this->reviewsCount);
        $otherCustomersProducts   = Product::getRandomProducts($otherCustomerProductCount);

        $productSections = [
            'related_to_viewed' => [
                'title'    => 'Related to Visited',
                'products' => $relatedToVisitedProducts,
                'reviews'  => count($relatedToVisitedProducts)
                    ? Review::getRandomReviews($productSectionReviewCount) : [],
            ],
            'best_choice' => [
                'title'    => 'Best Choice',
                'products' => $bestChoiceProducts,
                'reviews'  => count($bestChoiceProducts)
                    ? Review::getRandomReviews($productSectionReviewCount) : [],
            ],
            'random' => [
                'title'    => '',
                'products' => $randomProducts,
            ],
        ];

        $topProductBlocks = [
            'most_popular' => [
                'title'    => "Top {$bestSellingProductsCount} most popular",
                'products' => $mostPopularProducts,
            ],
            'best_selling' => [
                'title'    => "Top {$bestSellingProductsCount} best selling",
                'products' => $bestSellingProducts,
            ],
        ];

        return $this->view('home.home', [
            'rnd_products'             => $rndProducts,
            'relatedToVisitedProducts' => $relatedToVisitedProducts,
            'bestChoiceProducts'       => $bestChoiceProducts,
            'mostPopularProducts'      => $mostPopularProducts,
            'bestSellingProducts'      => $bestSellingProducts,
            'randomProducts'           => $randomProducts,
            'special_offers'           => $specialOffers,
            'selectedReviews'          => $selectedReviews,
            'otherCustomersProducts'   => $otherCustomersProducts,
            'productSections'          => $productSections,
            'topProductBlocks'         => $topProductBlocks,
            'message'                  => 'Index page',
        ]);
    }

    public function notFound()
    {
        return $this->view('404', ['message' => 'Index page']);
    }
}
