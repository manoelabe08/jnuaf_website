<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class CommitteePoleProposal extends Model

{

    protected $table = 'committee_pole_proposals';



    protected $guarded = [];



    protected function casts(): array

    {

        return ['expires_at' => 'datetime', 'answered_at' => 'datetime'];

    }



    public static function syncCardLabel(CommitteeApplication $application, ?string $from, string $to): void

    {

        if (! $from || $from === $to) {

            return;

        }

        foreach (MemberCard::where('source_type', 'committee')->where('source_id', $application->id)->get() as $card) {

            if ($card->role_label && str_contains($card->role_label, $from)) {

                $card->forceFill(['role_label' => str_replace($from, $to, $card->role_label)])->save();

            }

        }

    }

}

