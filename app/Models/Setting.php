<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Setting extends Model {
 protected $guarded=[]; protected $hidden=['value'];
 protected function casts():array{return ['is_encrypted'=>'boolean'];}
 public function getDecodedValue():mixed { $value=$this->is_encrypted?decrypt($this->value):$this->value;return match($this->type){'integer'=>(int)$value,'boolean'=>filter_var($value,FILTER_VALIDATE_BOOL),'json'=>json_decode($value,true),default=>$value}; }
 public function setDecodedValue(mixed $value):void { $serialized=match($this->type){'json'=>json_encode($value,JSON_THROW_ON_ERROR),'boolean'=>$value?'true':'false',default=>(string)$value};$this->value=$this->is_encrypted?encrypt($serialized):$serialized; }
}
