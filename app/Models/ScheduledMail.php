<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class ScheduledMail extends Model

{

    protected $guarded = ['id'];



    protected function casts(): array

    {

        return [

            'recipients' => 'encrypted:array',

            'body' => 'encrypted',

            'attachments' => 'array',

            'individual' => 'boolean',

            'scheduled_at' => 'datetime',

            'sent_at' => 'datetime',

        ];

    }

}

