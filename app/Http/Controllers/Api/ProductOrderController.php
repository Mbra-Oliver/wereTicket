<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\PageHeading;
use App\Models\Language;
use App\Models\ShopManagement\ProductOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductOrderController extends Controller
{
    public function product_order(Request $request)
    {
        // Authenticate customer
        $customer = Auth::guard('sanctum')->user();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $locale = $request->header('Accept-Language');
        $language = $locale ? Language::where('code', $locale)->first() : Language::where('is_default', 1)->first();

        $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('customer_order_page_title')->first();
        $data['orders'] = ProductOrder::where('user_id', $customer->id)->orderBy('id', 'desc')->get()
            ->map(function ($order) {
                $order->invoice_number = asset('assets/admin/file/order/invoices/' . $order->invoice_number);
                return $order;
            });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
    //details
    public function product_order_details(Request $request)
    {
        // Authenticate customer
        $customer = Auth::guard('sanctum')->user();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $locale = $request->header('Accept-Language');
        $language = $locale ? Language::where('code', $locale)->first() : Language::where('is_default', 1)->first();

        $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('customer_order_details_page_title')->first();
        $order = ProductOrder::where([['user_id', $customer->id], ['id', $request->order_id]])->orderBy('id', 'desc')->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ]);
        }

        $order->invoice_number = asset('assets/admin/file/order/invoices/' . $order->invoice_number);

        $data['order'] = $order;

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
