<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'devices';
    protected $primaryKey = 'id_device';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id_device', 'nama_device'];

    // Relasi ke data sensor (One-to-Many)
    public function sensorData()
    {
        return $this->hasMany(SensorData::class, 'id_device', 'id_device');
    }
}