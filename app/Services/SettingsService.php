<?php
namespace App\Services;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
final class SettingsService { public function get(string $key,mixed $default=null):mixed{return Cache::remember("setting.{$key}",300,fn()=>Setting::where('key',$key)->first()?->getDecodedValue()??$default);} public function put(string $group,string $key,mixed $value,string $type='string',bool $encrypted=false):void{$setting=Setting::firstOrNew(['key'=>$key]);$setting->fill(['group'=>$group,'type'=>$type,'is_encrypted'=>$encrypted]);$setting->setDecodedValue($value);$setting->save();Cache::forget("setting.{$key}");} }
