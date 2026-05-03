<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ReviewController extends Controller
{
    /*
    - الزبون يقيَم الخدمة أو المنتج الذي اشتراه من خلال الموقع
    - الزبون لا يمكنه تقييم خدمة أو منتج لم يشتريه
    - الزبون لا يمكنه تقييم خدمة أو منتج أكثر من مرة واحدة
    - الزبون يمكنه تحديث تقييمه أو حذفه إذا رغب في ذلك
    */ 
 
    //  إنشاء تقييم 
    public function store(Request $request)
    {
     $user = Auth::user();  // تجلب المستخدم الحالي الذي قام بتسجيل الدخول
     $validated = $request->validate([
        'reviewable_id'=>'required|integer',  // معرف المنتج ظاو الخدمة
        'reviewable_type'=>'required|string|in:App\Models\Product,App\Models\Service', // نوع العنصر
        'rating'=>'required|integer|min:1|max:5',
        'comment'=>'nullable|string|max:500',
     ]);

    //  الآن نتأكد من العنصر الذي نريد تقييمه هل هو موجود أم لا (منتج أم خدمة)
    $reviewable = null;
    if($validated['reviewable_type'] === 'App\Models\Product') {
            $reviewable = Product::find($validated['reviewable_id']);
        }

        elseif($validated['reviewable_type'] === 'App\Models\Service') {
            $reviewable = Service::find($validated['reviewable_id']);
       }
       if(!$reviewable)
        {
        return response()->json(
        [
        'success'=>false,
        'message'=>'العنصر المراد تقييمه غير موجود' 
        ] , 403);
        }
    //     منع تقييم العنصر أكثر من مرة من نفس المستخدم
      $existingReview = Review::where('user_id' , $user->id)
      ->where('reviewable_id', $validated['reviewable_id'])
      ->where('reviewable_type', $validated['reviewable_type'])
      ->first();

      if($existingReview)
        {
                 return response()->json([
                'success' => false,
                'message' => 'لقد قمت بتقييم هذا العنصر مسبقًا'
            ], 409);
        }
        //  إنشاء التقييم
        $review = Review::create(
            [
            'user_id'         => $user->id,
            'reviewable_id'   => $validated['reviewable_id'],
            'reviewable_type' => $validated['reviewable_type'],
            'rating'          => $validated['rating'],
            'comment'         => $validated['comment'],
            ]);
            //    بعد إنشاء التقييم يرجعلي الرد التالي
              return response()->json([
            'success' => true,
            'message' => 'تم إضافة تقييمك بنجاح، شكرًا لك!',
            'data'    => $review->load('user:id,first_name,last_name')
        ], 201);
    }


    // جلب التقييمات , اي جلب  تقييم المنتجات أو الخدمات بناءا على الرابط الذي تم استدعائه
    public function index(Request $request , $type , $id)
    {
    //   حطينا النوع من أجل معرفة ما هو الشي الذي نقوم بتقييمه الآن 
 $model = null;
        if ($type === 'products') {
            $model = Product::class;
        }
         elseif ($type === 'services') {
            $model = Service::class;
        }
         else {
            return response()->json([
                'success' => false,
                'message' => 'نوع غير مدعوم. استخدم products أو services'
            ], 400);
        }

        //  الآن نتحقق من وجود العنصر 
        $item = $model::find($id);
        if(!$item)
            {
              return response()->json(
                [
                    'success'=>false,
                    'message'=>'العنصر المطلوب غير موجود'
                ] , 404);
            }
                //   جلب التقييمات 
             $reviewsQuery = Review::where('reviewable_type', $model)
            ->where('reviewable_id', $id)
            ->with('user:id,first_name,last_name') // جلب بيانات المستخدم البسيطة
            ->orderBy('created_at', 'desc');
         
            $reviews = $reviewsQuery->paginate(10); // اي أظهر كل عشرة تقييمات في صفحة لحال
            // نحسب متوسط التقييم لهذا العنصر 
             $averageRating = Review::where('reviewable_type', $model)
            ->where('reviewable_id', $id)
            ->avg('rating');

        return response()->json([
            'success' => true,
            'data' => [
                'item_id' => $id,
                'item_type' => $type,
                'average_rating' => round($averageRating ?? 0, 1),
                'total_reviews' => $reviews->total(),
                'reviews' => $reviews
            ]] , 200);
      }
    
    
      // حذف التقييم من قِبل العميل
      public function delete($id)
      { 
       $user = Auth::user();
       $review = Review::find($id);
         if(!$review)
          {
                return response()->json([
                 'success' => false,
                 'message' => 'التقييم غير موجود'
                ], 404);
          }
          //  تأكد أن التقييم الذي يريد العميل حذفه هو تقييمه الخاص
          if($review->user_id !== $user->id)
          {
                return response()->json([
                 'success' => false,
                 'message' => 'ليس لديك صلاحية حذف هذا التقييم'
                ], 403);
          }
          $review->delete();
          return response()->json([
                'success' => true,
                'message' => 'تم حذف تقييمك بنجاح'
          ], 200);
      }    

  }
