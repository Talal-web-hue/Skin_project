<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
//   create a service
 public function store(Request $request)
 {
  if(Auth::user()->role !=='admin')
    {
    return response()->json(
        [
            'message'=>'غير مصرح لك بإنشاء خدمة , الخدمات يقوم بإنشائها المدير فقط',
            'success'=>false
        ] , 403);
    }

    $validated = $request->validate(
        [
            'name'=>'required|string|max:100',
            'description'=>'required|string|max:500',
            'duration'=>'required|integer|min:10',   // المدة الزمنية للجلسة أقل شي عشرة دقائق
            'price'=>'required|numeric',
            'is_active'=>'boolean|nullable'   // هل الجلسة متاحة أم لا
        ]);
        $service = Service::create($validated);
        return response()->json(
            [
                'message'=>'تم إنشاء المهمة بنجاح',
                'success'=>true,
                'date'=>$service
            ] , 201);
 }

//  لجلب الخدمات المتاحة و البحث عن خدمة حسب اسمها
  public function index(Request $request)
  {
   $query = Service::where('is_active' , true);
   if($request->filled('name'))
    {
    $query->where('name' , 'like','%', '%' .$request->name. '%');
    }

    $services = $query->paginate(10); // تابع ال paginate يقوم بإرجاع كل 10 خدمات في صفحة واحدة
    return response()->json(
        [
            'message'=>'الخدمات المتاحة لدينا هي :' ,
            'success'=>true ,
            'services'=>$services
        ] , 200);
  }




 /* التعديل على الخدمة يقوم بها المدير فقط 
  لإن الأخصائي يقوم بتنفيذ الخدمة فقط و لا يملك صلاحية تغيير سعرها أو تفاصيلها
   
 */
  public function update(Request $request , $id)
  {
    if(Auth::user()->role !=='admin')
        {
            return response()->json(
                [
                    'success'=>false , 'message'=>'غير مصرح'
                ] , 403);
        }
        $service = Service::find($id);  // هي مشان اتحقق من الخدمة هل موجودة أم لا
        if(!$service)
            {
             return response()->json(
                [
                    'message'=>'the service is not found'
                ] , 403);
            }
           $validated = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'duration'    => 'sometimes|integer|min:10',
            'price'       => 'sometimes|numeric',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'sometimes|boolean'
        ]);
        $service->update($validated);
        return response()->json(
            [
                'success'=>true ,
                'message'=>'تم تحديث الخدمة بنجاح' ,
                'the new service'=>$service
            ]);

  }

//    حذف الخدمة (طبعا المدير يقوم بحذف الخدمة فقط)
//  إذا كانت الخدمة لها موعد محجوز لا يمكن حذفها
 
public function destroy($id)
{
  if(Auth::user()->role !=='admin')
    {
     return response()->json(
        [
            'success'=>false ,
            'message'=>'غير مصرح لك'            
        ] , 403);
    }
    $service = Service::findOrFail($id);
    //  منع الحذف إذا كانت الخدمة محجوزة في مواعيد
    if($service->appointments()->exists())
        {
        return response()->json(
            [
                'success'=>false ,
                'message'=>'عذراً! لا يمكن حذف خدمة لها مواعيد' ,

            ]  , 403); 
        }

        $service->delete();
        return response()->json(
            [
                'message'=>'تم حذف الخدمة بنجاح'
            ] , 200);
    }

 
    //  تابع البحث عن خدمة حسب الوصف

    public function search(Request $request)
    {
     $query = Service::where('is_active' , true);
     if($request->filled('name'))     // البحث بالاسم
        {
        $query->where('name' , 'like' ,'%'. $request->name.'%');
        }

        // البحث بالوصف
        if($request->filled('description'))
            {
        $query->where('description' , 'like' , '%' . $request->description .'%');
            }

            return response()->json(
                [
                    'success'=>true,
                    'message'=>'نتائج البحث هي :',
                    'data'=>$query->orderBy('created_at' , 'desc')->paginate(10)
                ] , 200);
    }
  }
