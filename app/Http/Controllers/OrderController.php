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
                    'unit_price' => $item['price'],
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


//    تابع إلغاء الطلب من قبل العميل
   public function cancelOrder(Request $request , $id)
   {
   $user = Auth::user();
   $order = Order::with('orderItmes')->findOrFail($id);  // هنا قمنا بجلب الطلب مع العناصر المرتبطة به
    if($user->role !== 'client' || $order->user_id !== $user->id)
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
}