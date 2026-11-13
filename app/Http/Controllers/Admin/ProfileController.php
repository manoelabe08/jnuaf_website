<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\User;

use App\Services\AvatarService;
use App\Services\EmailChangeService;
use App\Services\TotpService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;



class ProfileController extends Controller

{

    public function edit(Request $request, EmailChangeService $emailChanges, TotpService $totp)

    {

        return view('admin.profile.edit', [

            'user' => $request->user()->loadMissing('territory'),

            'canEndSessions' => config('session.driver') === 'database',
            'devices' => \App\Models\KnownDevice::where('user_id', $request->user()->id)->orderByDesc('last_seen_at')->limit(20)->get(),
            'emailChange' => $emailChanges->pendingFor($request->user()),
            'totpKey' => $totpKey = $totp->pendingSecret($request->session()),
            'totpQr' => $totpKey ? $totp->qrSvg($totp->uri($request->user(), $totpKey)) : null,

        ]);

    }



    public function update(Request $request): RedirectResponse

    {

        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:120']]);

        $user = $request->user();

        $previous = $user->name;



        $user->forceFill(['name' => trim($data['name'])])->save();

        Log::info('Profil : nom modifié', ['user_id' => $user->id, 'ancien' => $previous, 'nouveau' => $user->name]);



        return back()->with('status', 'Profil mis à jour.');

    }



    public function photo(Request $request, AvatarService $avatars): RedirectResponse

    {

        $request->validate(['photo' => ['required', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp']], [

            'photo.required' => 'Choisissez une image.',

            'photo.max' => 'La photo doit faire 4 Mo au plus.',

            'photo.mimes' => 'Formats acceptés : JPG, PNG ou WebP.',

        ]);



        $user = $request->user();

        $name = $avatars->save($user, $request->file('photo'));

        $previous = $user->avatar_path;



        $user->forceFill(['avatar_path' => $name])->save();

        $avatars->delete($previous);



        return back()->with('status', 'Photo de profil mise à jour.');

    }



    public function removePhoto(Request $request, AvatarService $avatars): RedirectResponse

    {

        $user = $request->user();

        $previous = $user->avatar_path;



        $user->forceFill(['avatar_path' => null])->save();

        $avatars->delete($previous);



        return back()->with('status', 'Photo de profil supprimée.');

    }



    public function avatar(Request $request, User $user, AvatarService $avatars)

    {

        $path = $avatars->path($user->avatar_path);

        abort_if($path === null, 404);



        return response()->file($path, [

            'Content-Type' => 'image/webp',

            'Cache-Control' => 'private, max-age=86400',

            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",

        ]);

    }



    public function forgetDevice(Request $request, int $device): RedirectResponse
    {
        \App\Models\KnownDevice::where('user_id', $request->user()->id)->whereKey($device)->delete();

        return redirect()->route('admin.profile')->with('status', 'Appareil retiré de la liste.');
    }

    public function endOtherSessions(Request $request): RedirectResponse

    {

        $request->validate(['current_password' => ['required', 'current_password']], [

            'current_password.required' => 'Saisissez votre mot de passe pour confirmer.',

            'current_password.current_password' => 'Le mot de passe est incorrect.',

        ]);



        abort_unless(config('session.driver') === 'database', 422, 'Cette fonction nécessite les sessions en base de données.');



        $user = $request->user();

        $closed = DB::table(config('session.table', 'sessions'))

            ->where('user_id', $user->id)

            ->where('id', '!=', $request->session()->getId())

            ->delete();



        $user->forceFill(['remember_token' => Str::random(60)])->save();

        Log::info('Profil : autres sessions fermées', ['user_id' => $user->id, 'sessions' => $closed]);



        return back()->with('status', $closed.' autre(s) session(s) fermée(s).');

    }

}

