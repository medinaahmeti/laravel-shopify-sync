<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = ['domain', 'access_token'];
    protected $hidden = ['access_token'];

    public function getDecryptedToken(): string
    {
        return Crypt::decryptString($this->access_token);
    }
}
