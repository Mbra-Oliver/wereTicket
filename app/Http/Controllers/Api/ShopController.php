<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\PageHeading;
use App\Models\Language;
use App\Models\ShopManagement\OrderItem;
use App\Models\ShopManagement\Product;
use App\Models\ShopManagement\ProductCategory;
use App\Models\ShopManagement\ProductImage;
use App\Models\ShopManagement\ProductOrder;
use App\Models\ShopManagement\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->header('Accept-Language');
        $language = $locale ? Language::where('code', $locale)->first() : Language::where('is_default', 1)->first();

        $information['page_title'] = PageHeading::where('language_id', $language->id)->pluck('shop_page_title')->first();

        $information  = [];
        $product_categories = ProductCategory::where([['status', 1], ['language_id', $language->id]])->get();

        $category = $search = $min = $max = null;
        if ($request->filled('category')) {
            $category = $request['category'];
            $category = ProductCategory::where('slug', $category)->first();
            $category = $category->id;
        }
        if ($request->filled('search')) {
            $search = $request['search'];
        }
        if ($request->filled('min') && $request->filled('max')) {
            $min = $request['min'];
            $max = $request['max'];
        }
        if ($request->filled('product_short')) {
            if ($request['product_short'] == 'new') {
                $order_by_column = 'products.id';
                $order = 'desc';
            } elseif ($request['product_short'] == 'default') {
                $order_by_column = 'products.id';
                $order = 'desc';
            } elseif ($request['product_short'] == 'old') {
                $order_by_column = 'products.id';
                $order = 'asc';
            } elseif ($request['product_short'] == 'hight-to-low') {
                $order_by_column = 'products.current_price';
                $order = 'desc';
            } elseif ($request['product_short'] == 'low-to-high') {
                $order_by_column = 'products.current_price';
                $order = 'asc';
            }
        } else {
            $order_by_column = 'products.id';
            $order = 'desc';
        }

        $products = Product::join('product_contents', 'product_contents.product_id', '=', 'products.id')
            ->join('product_categories', 'product_categories.id', '=', 'product_contents.category_id')
            ->where('product_contents.language_id', '=', $language->id)
            ->when($category, function ($query, $category) {
                return $query->where('product_contents.category_id', '=', $category);
            })
            ->when($search, function ($query, $keyword) {
                return $query->where('product_contents.title', 'like', '%' . $keyword . '%');
            })
            ->when(($min && $max), function ($query) use ($min, $max) {
                return $query->where('products.current_price', '>=', $min)->where('products.current_price', '<=', $max);
            })
            ->where('products.status', 1)
            ->select('products.*', 'product_contents.id as productInfoId', 'product_contents.title', 'product_contents.slug', 'product_categories.name as category')
            ->orderBy($order_by_column, $order)
            ->get();

        $productsCollection = $products->map(function ($product) {
            $product->feature_image = asset('assets/admin/img/product/feature_image/' . $product->feature_image);
            return $product;
        });

        $information['products'] = $productsCollection;
        $information['product_categories'] = $product_categories;

        $max = Product::max('current_price');
        $min = Product::min('current_price');
        $information['max'] = $max;
        $information['min'] = $min;

        return response()->json([
            'success' => true,
            'data' => $information
        ]);
    }

    public function details(Request $request)
    {
        $locale = $request->header('Accept-Language');
        $language = $locale ? Language::where('code', $locale)->first() : Language::where('is_default', 1)->first();

        $information = [];
        $product = Product::join('product_contents', 'product_contents.product_id', '=', 'products.id')
            ->join('product_categories', 'product_categories.id', '=', 'product_contents.category_id')
            ->where('product_contents.language_id', '=', $language->id)
            ->select('products.*', 'product_contents.id as productInfoId', 'product_contents.title', 'product_contents.description', 'product_contents.summary', 'product_contents.meta_keywords', 'product_contents.meta_description', 'product_contents.slug',  'product_categories.name as category', 'product_categories.slug as slug')
            ->where('products.id', $request->product_id)
            ->first();
        $product->feature_image = asset('assets/admin/img/product/feature_image/' . $product->feature_image);
        if (empty($product)) {
            return response()->json([
                'success' => false,
                'message' => 'The product is not found'
            ]);
        }
        $information['product'] = $product;
        $product_gallery = ProductImage::where('product_id',  $request->product_id)
            ->get()
            ->map(function ($gallery) {
                return [
                    'image' => asset('assets/admin/img/product/gallery/' . $gallery->image)
                ];
            });
        $information['galleries'] = $product_gallery;

        $information['reviews'] = ProductReview::where('product_id', $request->product_id)->get();

        return response()->json([
            'success' => true,
            'data' => $information
        ]);
    }

    //review
    public function store_review(Request $request)
    {
        // Authenticate customer
        $customer = Auth::guard('sanctum')->user();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $rules = [
            'review' => 'required:numeric',
            'product_id' => 'required:numeric',
            'comment' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product_order = OrderItem::where('user_id', $customer->id)->where('product_id', $request->product_id)->exists();
        if (!$product_order) {
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => __("Please purchase this product first")
                ], 422);
            }
        }

        $review = ProductReview::where([['user_id', $customer->id], ['product_id', $request->product_id]])->first();

        if ($review) {
            if ($request->review) {
                $review->update([
                    'review' => $request->review,
                ]);
            }
            if ($request->comment) {
                $review->update([
                    'comment' => $request->comment,
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => __('Review update successfully'),
                'review' => $review
            ]);
        } else {
            $input = $request->all();
            $input['product_id'] = $request->product_id;
            $input['user_id'] = $customer->id;
            $data = new ProductReview;
            $data = $data->create($input);

            return response()->json([
                'success' => true,
                'message' => __('Review saved successfully'),
                'review' => $data
            ]);
        }
    }
}
