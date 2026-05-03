<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
  // إنشاء طلب جديد متاح للآدمن والزبون فقط
//    الأخصائي دوره يركز على الخدمات و المواعيد الطبية
//   الآدمن ممكن أن يقوم بعملية الحجز عند الطلب الهاتفي او زيارة المركز
    public function store(Request $request)
    {
        $user = Auth::user();

        if (! in_array($user->role, ['admin', 'client'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك لإنشاء طلب'
            ], 403);
        }

        $validated = $request->validate([
            'order_date' => 'required|date',
            'status' => 'sometimes|in:confirmed,cancelled,pending',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'user_id' => $user->role === 'admin' ? 'sometimes|exists:users,id' : 'prohibited',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($validated, $user) {
            $order = Order::create([
                'user_id' => $user->role === 'admin' ? ($validated['user_id'] ?? $user->id) : $user->id,
                'order_date' => $validated['order_date'],
                'status' => $validated['status'] ?? 'pending',
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'],
                'total_amount' => $validated['total_amount'],
            ]);

            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],  // السعر وقت الشراء
                ]);
            }

            return $order;
        });

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح',
            'data' => $order->load('orderItems.product')
        ], 201);
    }

    //  لعرض الطلبات او جلب طلبات العميل
    public function index()
    {
        $user = Auth::user();    // لجلب السمتخدم الذي قام بتسجيل الدخول
        if ($user->role === 'admin') {
            $orders = Order::with([
                'user:id,first_name,last_name,email',
                'orderItems.product:id,name'
            ])->orderBy('order_date', 'desc')->paginate(10);  // بعد جلب الطلبات قم بترتيب الطلبات حسب تاريخ الطلب
        } elseif ($user->role === 'client') {
            $orders = Order::where('user_id', $user->id)
                ->with(['orderItems.product:id,name'])
                ->orderBy('order_date', 'desc')
                ->paginate(10);

                //  في حال لم يتم تنفيذ الشرطين السابقين قم بالانتقال إلى هنا
        } else {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بمشاهدة الطلبات'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $orders
        ], 200);
    }
    

    // لجلب طلب ما للعميل
    public function show($orderId)
    {
        $user = Auth::user();

        $order = Order::with([
            'user:id,first_name,last_name,email',
            'orderItems.product:id,name'
        ])->findOrFail($orderId);
        // في حال لم تكن آدمن او زبون لا يحق لك مشاهدة الطلب
        if ($user->role !== 'admin' && $order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بمشاهدة هذا الطلب'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ], 200);
    }


    //  لتحديث حالة الطلب طبعا الآدمن هو يلي يقوم بهي الخاصية 
    // يعني بعدما تم بيع المنتج مثلا عندها الآدمن يقوم يتحديث حالة الطلب
    // اي تحديث حالة الطلب من معلق مثلا إلى مؤكد
    public function orderUpdateStatus(Request $request , $orderId)
    { 
     if(Auth::user()->role !=='admin')
        {
        return response()->json(
            [
                'success'=>false,
                'message'=>'غير مصرح لك بتحديث حالة الطلب , هذه العملية مخصصة للإدارة فقط'
            ] , 403);
        }
        //  الآن نتحقق من صحة الحالة
        $validated = $request->validate([
            'status'=>'required|in:pending,cancelled,confirmed'
        ]);
        $order = Order::findOrFail($orderId);
        if($order->status ==='cancelled')
            {
            return response()->json([
             'success'=>false,
             'message'=>'لا يمكنك تحديث حالة طلب تم إلغائه مسبقاً'
            ] , 403); 
            }
            $order->update(['status'=>$validated['status']]);
            return response()->json(
                [
                    'success'=>true,
                    'message'=>'تم تحديث حالة الطلب بنجاح'
                ] , 200);
}


//    تابع يسمح للعميل بإلغاء طلبه مع إعادة المنتجات للمخزون تلقائيا
   public function cancelOrder(Request $request , $id)
   {
   $user = Auth::user();
   $order = Order::with('orderItems')->findOrFail($id);  // هنا قمنا بجلب الطلب مع العناصر المرتبطة به
    if($user->role !== 'client' || $order->user_id !== $user->id) // يتحقق أن المستخدم عميل و أن الطلب له
    {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بإلغاء هذا الطلب'
        ], 403);
    }

    if($order->status === 'cancelled')
    {
        return response()->json([
            'success' => false,
            'message' => 'هذا الطلب تم إلغائه مسبقاً'
        ], 403);
    }
    DB::transaction(function () use ($order) {
        $order->update(['status' => 'cancelled']);   // تحديث حالة الطلب إلى ملغي
        foreach ($order->orderItems as $item) {   // هنا إعادة الكميات المخزنة للمنتجات
            Product::where('id' , $item->product_id)
            ->increment('quantity', $item->quantity);  // إعادة الكمية إلى المخزون
        }
    });
    return response()->json(
        [
            'success'=>true,
            'message'=>'تم إلغاء حالة الطلب بنجاح وإعادة الكمية للمخزون'
        ] , 200);
}

//   حذف طلب , و هي صلاحية للآدمن فقط
public function delete($id)
{
    if(Auth::user()->role !== 'admin')
    {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بحذف هذا الطلب'
        ], 403);
    }
    $order = Order::findOrFail($id);
    $order->delete();
    return response()->json([
        'success' => true,
        'message' => 'تم حذف الطلب بنجاح'
    ], 200);
}


//  تابع حساب الفاتورة 
public function calculateInvoice(Request $request)
{
   // التحقق من المدخلات
        $validated = $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:products,id',
            'items.*.quantity'  => 'required|integer|min:1',
            'discount_code'     => 'nullable|string|max:50',      // كوبون خصم اختياري
            'shipping_address'  => 'nullable|string|max:500',     // لحساب تكلفة الشحن
            'tax_rate'          => 'nullable|numeric|min:0|max:100', // نسبة الضريبة (مثلاً 15)
        ]);

        $subtotal = 0;
        $invoiceItems = [];
        $availableProducts = [];

      // جلب المنتجات وحساب المجموع الجزئي
        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            
            // فحص المخزون وإعلام المستخدم
            $inStock = $product->stock_quantity >= $item['quantity'];
            
            $lineTotal = $product->price * $item['quantity'];
            $subtotal += $lineTotal;
            
            $invoiceItems[] = [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'quantity'     => $item['quantity'],
                'unit_price'   => $product->price,
                'line_total'   => $lineTotal,  // هو مجموع اسعار المنتجات 
                'in_stock'     => $inStock,  // اذا كان المنتج متوفر تكون قيمة هذا المتغير هي true
                'message'      => $inStock ? 'متوفر' : "الكمية المتاحة: {$product->stock_quantity}"
            ];
            
            $availableProducts[$product->id] = $product;
        }

        //  حساب الخصم (كوبون)
        $discountAmount = 0;
        $discountMessage = null;
        
        if (!empty($validated['discount_code'])) {
            // هنا يعطي 10 بالمية كعملية خصم
            $coupons = [
                'WELCOME10' => ['type' => 'percent', 'value' => 10],
                'SAVE50'    => ['type' => 'fixed', 'value' => 50],
            ];
            
            $code = strtoupper($validated['discount_code']);
            if (isset($coupons[$code])) {
                $coupon = $coupons[$code];
                if ($coupon['type'] === 'percent') {
                    $discountAmount = $subtotal * ($coupon['value'] / 100);
                } else {
                    $discountAmount = min($coupon['value'], $subtotal); // لا يتجاوز المجموع
                }
                $discountMessage = "تم تطبيق كوبون {$code}";
            } else {
                $discountMessage = "كوبون غير صالح";
            }
        }

        //  حساب تكلفة الشحن
        $shippingCost = 0;
        if (!empty($validated['shipping_address'])) {
            // مثال: شحن مجاني للطلبات فوق 300، وإلا 25 
            $shippingCost = ($subtotal - $discountAmount >= 300) ? 0 : 25;
        }

        //حساب الضريبة)
        $taxRate = $validated['tax_rate'] ?? 0; // نسبة الضريبة (مثلاً 15 لـ 15%)
        $taxableAmount = max(0, $subtotal - $discountAmount);
        $taxAmount = $taxableAmount * ($taxRate / 100);

        // الحساب النهائي
        $total = $taxableAmount + $shippingCost + $taxAmount;

        // 📤 7. إرجاع الفاتورة المفصلة
        return response()->json([
            'success' => true,
            'data' => [
                'items' => $invoiceItems,
                'summary' => [
                    'subtotal'       => round($subtotal, 2),
                    'discount'       => round($discountAmount, 2),
                    'discount_note'  => $discountMessage,
                    'shipping'       => round($shippingCost, 2),
                    'tax_rate'       => $taxRate . '%',
                    'tax_amount'     => round($taxAmount, 2),
                    'total'          => round($total, 2),
                    'currency'       => 'SAR'   // نوع العملة , هنا عندي العملة السورية مثلا 
                ],
                'warnings' => collect($invoiceItems)->filter(fn($i) => !$i['in_stock'])->pluck('message')->toArray(),
                'can_checkout' => collect($invoiceItems)->every(fn($i) => $i['in_stock']) && $total > 0  // بتأكد من أن قمية الفاتورة هي أكبر من الصفر و يتحقق أيضا من ان العنصر هل هو متاح أم لا 
            ]
        ]);

}
}