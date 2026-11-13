<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CommitteeApplicationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PartnerInquiryController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminMailboxController;

/* ---------- Site public ---------- */
Route::get('/', HomeController::class)->name('home');

Route::middleware('throttle:public-forms')->group(function () {
    Route::post('/inscription', [RegistrationController::class, 'store'])->name('registration.store');
    Route::post('/comite/candidature', [CommitteeApplicationController::class, 'store'])->name('committee.store');
    Route::post('/partenaires/contact', [PartnerInquiryController::class, 'store'])->name('partners.store');
});

// Espace personnel : accessible uniquement avec le jeton reçu par email.
Route::prefix('mon-inscription/{token}')->name('registration.')
    ->where(['token' => '[A-Za-z0-9]{48}'])
    ->middleware('throttle:30,1')
    ->group(function () {
        Route::get('/', [RegistrationController::class, 'manage'])->name('manage');
        Route::get('/badge', [RegistrationController::class, 'badge'])->name('badge');
        Route::get('/attestation', [RegistrationController::class, 'attestation'])->name('attestation');
        Route::patch('/', [RegistrationController::class, 'update'])->name('update');
        Route::post('/annuler', [RegistrationController::class, 'cancel'])->name('cancel');
        Route::delete('/', [RegistrationController::class, 'destroy'])->name('destroy');
    });

/* ---------- Authentification ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/admin/connexion', [LoginController::class, 'show'])->name('login');
    Route::post('/admin/connexion', [LoginController::class, 'login']);

    Route::get('/admin/mot-de-passe-oublie', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/admin/mot-de-passe-oublie', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/admin/reinitialiser/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/admin/reinitialiser', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('/admin/deconnexion', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/* ---------- Administration ---------- */
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    // Bureau de Pilotage et coordinations (données limitées à leur territoire).
    Route::get('/', Admin\DashboardV2Controller::class)->name('dashboard');
    Route::get('/old_dashboard', Admin\DashboardController::class)->name('dashboard.old');
    Route::resource('evenements', Admin\EventController::class)->parameters(['evenements' => 'event'])->names('events')->except(['show', 'destroy']);
    Route::delete('/evenements/{event}', [Admin\EventController::class, 'destroy'])->whereNumber('event')->middleware('sensitive:2,delete_event')->name('events.destroy');

    Route::get('/inscriptions', [Admin\RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('/inscriptions/export', [Admin\RegistrationController::class, 'export'])->middleware('password.confirm')->name('registrations.export');
    Route::get('/inscriptions/{registration}', [Admin\RegistrationController::class, 'show'])->name('registrations.show');
    Route::patch('/inscriptions/{registration}', [Admin\RegistrationController::class, 'update'])->name('registrations.update');
    Route::delete('/inscriptions/{registration}', [Admin\RegistrationController::class, 'destroy'])->middleware('sensitive:2,delete_registration')->name('registrations.destroy');
    Route::post('/inscriptions/{registration}/badge', [Admin\RegistrationController::class, 'resendBadge'])->name('registrations.resend');
    Route::get('/inscriptions/{registration}/badge', [Admin\RegistrationController::class, 'badge'])->name('registrations.badge');
    Route::get('/inscriptions/{registration}/attestation', [Admin\RegistrationController::class, 'attestation'])->name('registrations.attestation');



    Route::view('/pointage-camera', 'admin.checkin.camera')->name('checkin.camera');
    Route::get('/apercu/attestation', [\App\Http\Controllers\Admin\DocumentPreviewController::class, 'attestation'])->name('preview.attestation');
    Route::get('/apercu/carte', [\App\Http\Controllers\Admin\DocumentPreviewController::class, 'card'])->name('preview.card');
    Route::get('/cartes', [\App\Http\Controllers\Admin\MemberCardController::class, 'index'])->name('cards.index');
    Route::get('/cartes/nouvelle', [\App\Http\Controllers\Admin\MemberCardController::class, 'create'])->name('cards.create');
    Route::post('/cartes', [\App\Http\Controllers\Admin\MemberCardController::class, 'store'])->name('cards.store');
    Route::get('/cartes/{card}/pdf', [\App\Http\Controllers\Admin\MemberCardController::class, 'pdf'])->whereNumber('card')->name('cards.pdf');
    Route::post('/cartes/{card}/revoquer', [\App\Http\Controllers\Admin\MemberCardController::class, 'revoke'])->whereNumber('card')->name('cards.revoke');
    Route::post('/cartes/{card}/photo', [\App\Http\Controllers\Admin\MemberCardController::class, 'photo'])->whereNumber('card')->name('cards.photo');
    Route::post('/cartes/{card}/envoyer', [\App\Http\Controllers\Admin\MemberCardController::class, 'sendCard'])->middleware('throttle:20,1')->whereNumber('card')->name('cards.send');
    Route::get('/discussions/non-lus', [\App\Http\Controllers\Admin\ChatController::class, 'unread'])->middleware('throttle:60,1')->name('chat.unread');
    Route::post('/discussions/prive', [\App\Http\Controllers\Admin\ChatController::class, 'direct'])->middleware('throttle:20,1')->name('chat.direct');
    Route::get('/discussions/{room?}', [\App\Http\Controllers\Admin\ChatController::class, 'show'])->whereNumber('room')->name('chat.show');
    Route::get('/discussions/{room}/messages', [\App\Http\Controllers\Admin\ChatController::class, 'poll'])->middleware('throttle:120,1')->whereNumber('room')->name('chat.poll');
    Route::post('/discussions/{room}/messages', [\App\Http\Controllers\Admin\ChatController::class, 'send'])->middleware('throttle:30,1')->whereNumber('room')->name('chat.send');
Route::delete('/discussions/messages/{message}', [\App\Http\Controllers\Admin\ChatController::class, 'destroy'])->middleware('throttle:30,1')->whereNumber('message')->name('chat.messages.destroy');
Route::post('/discussions/messages/{message}/reactions', [\App\Http\Controllers\Admin\ChatController::class, 'react'])->middleware('throttle:60,1')->whereNumber('message')->name('chat.messages.react');
    Route::post('/discussions/{room}/appel', [\App\Http\Controllers\Admin\ChatController::class, 'call'])->middleware('throttle:10,1')->whereNumber('room')->name('chat.call');
    
Route::get('/discussions/messages/{message}/fichier', [\App\Http\Controllers\Admin\ChatController::class, 'attachment'])->middleware('throttle:120,1')->whereNumber('message')->name('chat.attachment');

    Route::post('/cartes/{card}/renouveler', [\App\Http\Controllers\Admin\MemberCardController::class, 'renew'])->whereNumber('card')->name('cards.renew');
    Route::get('/pointage', [Admin\CheckinController::class, 'index'])->name('checkin.index');
    Route::post('/pointage', [Admin\CheckinController::class, 'store'])->name('checkin.store');
    Route::get('/pointage/{token}', [Admin\CheckinController::class, 'token'])->name('checkin.token');

    // Réservé au Bureau de Pilotage.
    Route::middleware('admin')->group(function () {
        Route::get('/chatbot', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'index'])->name('chatbot.index');
        Route::post('/chatbot/activation', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'toggle'])->name('chatbot.toggle');
        Route::post('/chatbot/questions/{id}/repondre', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'answer'])->whereNumber('id')->name('chatbot.answer');
        Route::post('/chatbot/questions/{id}/ignorer', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'ignore'])->whereNumber('id')->name('chatbot.ignore');
        Route::post('/chatbot/demandes/{id}/traiter', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'close'])->whereNumber('id')->name('chatbot.close');
        Route::get('/chatbot/sources', [\App\Http\Controllers\Admin\ChatbotSourceController::class, 'index'])->name('chatbot.sources');
        Route::post('/chatbot/sources/pdf', [\App\Http\Controllers\Admin\ChatbotSourceController::class, 'storePdf'])->middleware('throttle:10,1')->name('chatbot.sources.pdf');
        Route::post('/chatbot/sources/url', [\App\Http\Controllers\Admin\ChatbotSourceController::class, 'storeUrl'])->middleware('throttle:10,1')->name('chatbot.sources.url');
        Route::post('/chatbot/sources/blog', [\App\Http\Controllers\Admin\ChatbotSourceController::class, 'syncPosts'])->middleware('throttle:10,1')->name('chatbot.sources.blog');
        Route::get('/chatbot/sources/{id}', [\App\Http\Controllers\Admin\ChatbotSourceController::class, 'show'])->whereNumber('id')->name('chatbot.sources.show');
        Route::post('/chatbot/sources/{id}/activer', [\App\Http\Controllers\Admin\ChatbotSourceController::class, 'toggle'])->whereNumber('id')->name('chatbot.sources.toggle');
        Route::post('/chatbot/sources/{id}/actualiser', [\App\Http\Controllers\Admin\ChatbotSourceController::class, 'refresh'])->whereNumber('id')->middleware('throttle:10,1')->name('chatbot.sources.refresh');
        Route::post('/attestations/envoyer', [Admin\RegistrationController::class, 'sendAttestations'])->name('attestations.send');

        Route::get('/versions', [Admin\SiteVersionController::class, 'index'])->name('versions.index');
        Route::get('/articles', [Admin\PostController::class, 'index'])->name('posts.index');
        Route::get('/articles/nouveau', [Admin\PostController::class, 'create'])->name('posts.create');
        Route::post('/articles', [Admin\PostController::class, 'store'])->name('posts.store');
        Route::get('/articles/{post}/modifier', [Admin\PostController::class, 'edit'])->name('posts.edit');
        Route::put('/articles/{post}', [Admin\PostController::class, 'update'])->name('posts.update');
        Route::post('/articles/{post}/publier', [Admin\PostController::class, 'publish'])->name('posts.publish');
        Route::post('/articles/{post}/depublier', [Admin\PostController::class, 'unpublish'])->name('posts.unpublish');
        Route::post('/articles/{post}/supprimer', [Admin\PostController::class, 'destroy'])->middleware('sensitive:2,delete_post')->name('posts.destroy');
        Route::post('/articles/masse', [Admin\PostController::class, 'bulk'])->name('posts.bulk');
        Route::post('/articles/upload-image', [Admin\PostController::class, 'uploadImage'])->name('posts.upload-image');
        Route::post('/versions/{id}/publier', [Admin\SiteVersionController::class, 'publish'])->whereNumber('id')->middleware('sensitive:2,publish_site_version')->name('versions.publish');
        Route::get('/pages', [Admin\PublicPageController::class, 'index'])->name('pages.index');
        Route::post('/pages/accueil', [Admin\PublicPageController::class, 'home'])->middleware('sensitive:1,switch_home_page')->name('pages.home');
        Route::post('/pages/{key}/statut', [Admin\PublicPageController::class, 'toggle'])->where('key', '[a-z_]+')->middleware('sensitive:1,toggle_public_page')->name('pages.toggle');
        Route::get('/parametres', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/parametres', [Admin\SettingsController::class, 'update'])->middleware('sensitive:2,update_settings')->name('settings.update');
Route::get('/messages', [Admin\MessageController::class, 'create'])->name('messages.create');
Route::post('/messages', [Admin\MessageController::class, 'send'])->middleware('throttle:6,1')->name('messages.send');

Route::get('/messagerie', [AdminMailboxController::class, 'inbox'])
    ->name('mailbox.inbox');

Route::get('/messagerie/envoyes', [AdminMailboxController::class, 'sent'])

    ->name('mailbox.sent');

Route::get('/messagerie/{folder}/{uid}', [\App\Http\Controllers\AdminMailMessageController::class, 'show'])

    ->whereIn('folder', ['inbox', 'sent'])

    ->whereNumber('uid')

    ->name('mailbox.show');

Route::get('/messagerie/{folder}/{uid}/piece/{index}', [\App\Http\Controllers\AdminMailMessageController::class, 'attachment'])

    ->whereIn('folder', ['inbox', 'sent'])

    ->whereNumber('uid')

    ->whereNumber('index')

    ->name('mailbox.attachment');



Route::get('/messagerie/nouveau', [AdminMailboxController::class, 'compose'])

    ->name('mailbox.compose');

Route::post('/messagerie/envoyer', [AdminMailboxController::class, 'send'])

    ->middleware('throttle:10,1')

    ->name('mailbox.send');

    
    
        Route::get('/territoires', [Admin\TerritoryController::class, 'index'])->name('territories.index');
        Route::post('/territoires', [Admin\TerritoryController::class, 'store'])->name('territories.store');
        Route::put('/territoires/{territory}', [Admin\TerritoryController::class, 'update'])->name('territories.update');
        Route::delete('/territoires/{territory}', [Admin\TerritoryController::class, 'destroy'])->middleware('sensitive:2,delete_territory')->name('territories.destroy');

        Route::get('/comite', [Admin\CommitteeApplicationController::class, 'index'])->name('committee.index');
        Route::get('/grades-comite', [Admin\CommitteeGradeController::class, 'index'])->name('grades.index');
        Route::patch('/grades-comite/{application}', [Admin\CommitteeGradeController::class, 'update'])->whereNumber('application')
->name('grades.update');

        Route::post('/grades-comite/{application}/compte', [Admin\CommitteeAccountController::class, 'store'])->whereNumber('application')->middleware(['throttle:10,1', 'sensitive:2,create_committee_account'])
->name('grades.account');

        Route::post('/grades-comite/invitations/apercu', [Admin\CommitteeInvitationController::class, 'preview'])->middleware('throttle:20,1')->name('grades.invite.preview');

        Route::post('/grades-comite/invitations/envoyer', [Admin\CommitteeInvitationController::class, 'send'])->middleware(['throttle:5,1', 'sensitive:1,invite_to_apply'])->name('grades.invite.send');

        Route::post('/grades-comite/invitations/annuler', [Admin\CommitteeInvitationController::class, 'cancel'])->name('grades.invite.cancel');


        Route::post('/corbeille/masse', [\App\Http\Controllers\Admin\TrashController::class, 'bulk'])->name('trash.bulk');
        Route::post('/comite/masse', [Admin\CommitteeApplicationController::class, 'bulk'])->middleware('throttle:10,1')->name('committee.bulk');
        Route::post('/partenaires/masse', [Admin\PartnerInquiryController::class, 'bulk'])->middleware('throttle:10,1')->name('partners.bulk');
        Route::post('/galerie/masse', [Admin\GalleryController::class, 'bulk'])->middleware('throttle:10,1')->name('gallery.bulk');
        Route::get('/audit-droits', [Admin\RightsAuditController::class, 'index'])->name('audit.rights');
        Route::post('/comite/{application}/pole', [Admin\CommitteePoleController::class, 'propose'])->whereNumber('application')->middleware('throttle:20,1')->name('committee.pole.propose');
        Route::post('/comite/{application}/pole/resoudre', [Admin\CommitteePoleController::class, 'resolve'])->whereNumber('application')->name('committee.pole.resolve');
        Route::get('/tableau-de-bord-2', Admin\DashboardV2Controller::class)->name('dashboard2');
        Route::get('/comite/export', [Admin\CommitteeApplicationController::class, 'export'])->middleware('password.confirm')->name('committee.export');
        Route::get('/comite/{application}', [Admin\CommitteeApplicationController::class, 'show'])->name('committee.show');
        Route::patch('/comite/{application}', [Admin\CommitteeApplicationController::class, 'update'])->name('committee.update');
        Route::delete('/comite/{application}', [Admin\CommitteeApplicationController::class, 'destroy'])->middleware('sensitive:2,delete_committee_application')->name('committee.destroy');
        Route::get('/comite/{application}/{document}', [Admin\CommitteeApplicationController::class, 'download'])
            ->whereIn('document', ['cv', 'lettre'])->name('committee.download');

        Route::get('/partenaires', [Admin\PartnerInquiryController::class, 'index'])->name('partners.index');
        Route::get('/partenaires/export', [Admin\PartnerInquiryController::class, 'export'])->middleware('password.confirm')->name('partners.export');
        Route::get('/partenaires/{inquiry}', [Admin\PartnerInquiryController::class, 'show'])->name('partners.show');
        Route::patch('/partenaires/{inquiry}', [Admin\PartnerInquiryController::class, 'update'])->name('partners.update');
        Route::delete('/partenaires/{inquiry}', [Admin\PartnerInquiryController::class, 'destroy'])->middleware('sensitive:2,delete_partner_inquiry')->name('partners.destroy');

        Route::get('/programme',[Admin\ProgramController::class, 'index'])->name('program.index');
        Route::post('/programme', [Admin\ProgramController::class, 'store'])->name('program.store');
        Route::put('/programme/{item}', [Admin\ProgramController::class, 'update'])->name('program.update');
        Route::delete('/programme/{item}', [Admin\ProgramController::class, 'destroy'])->name('program.destroy');

        Route::get('/intervenantes', [Admin\SpeakerController::class, 'index'])->name('speakers.index');
        Route::post('/intervenantes', [Admin\SpeakerController::class, 'store'])->name('speakers.store');
        Route::put('/intervenantes/{speaker}', [Admin\SpeakerController::class, 'update'])->name('speakers.update');
        Route::delete('/intervenantes/{speaker}', [Admin\SpeakerController::class, 'destroy'])->name('speakers.destroy');
Route::post('/galerie-lots/synchroniser-tout', [
    \App\Http\Controllers\Admin\GalleryFolderController::class,
    'syncAll',
])->name('gallery-folders.sync-all');
Route::get('/galerie-lots', [
    \App\Http\Controllers\Admin\GalleryFolderController::class,
    'index',
])->name('gallery-folders.index');

Route::post('/galerie-lots', [
    \App\Http\Controllers\Admin\GalleryFolderController::class,
    'store',
])->name('gallery-folders.store');

Route::put('/galerie-lots/{folder}', [
    \App\Http\Controllers\Admin\GalleryFolderController::class,
    'update',
])
    ->where('folder', '[a-z0-9][a-z0-9_-]{0,39}')
    ->name('gallery-folders.update');

Route::post('/galerie-lots/{folder}/synchroniser', [
    \App\Http\Controllers\Admin\GalleryFolderController::class,
    'sync',
])
    ->where('folder', '[a-z0-9][a-z0-9_-]{0,39}')
    ->name('gallery-folders.sync');
        Route::get('/galerie', [Admin\GalleryController::class, 'index'])->name('gallery.index');
        Route::post('/galerie', [Admin\GalleryController::class, 'store'])->name('gallery.store');
        Route::put('/galerie/{photo}', [Admin\GalleryController::class, 'update'])->name('gallery.update');
        Route::delete('/galerie/{photo}', [Admin\GalleryController::class, 'destroy'])->name('gallery.destroy');
        Route::get('/logos-partenaires', [Admin\SponsorLogoController::class, 'index'])->name('sponsors.index');
        Route::post('/logos-partenaires', [Admin\SponsorLogoController::class, 'store'])->name('sponsors.store');
        Route::put('/logos-partenaires/{logo}', [Admin\SponsorLogoController::class, 'update'])->name('sponsors.update');
        Route::delete('/logos-partenaires/{logo}', [Admin\SponsorLogoController::class, 'destroy'])->name('sponsors.destroy');

        Route::get('/comptes', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/comptes', [Admin\UserController::class, 'store'])->middleware('sensitive:2,create_user')->name('users.store');
        Route::put('/comptes/{user}', [Admin\UserController::class, 'update'])->middleware('sensitive:2,update_user')->name('users.update');
        Route::delete('/comptes/{user}', [Admin\UserController::class, 'destroy'])->middleware('sensitive:1,delete_user')->name('users.destroy');
        Route::get('/journal-securite', [Admin\SecurityLogController::class, 'index'])->name('security.index');
    });
});
require __DIR__.'/partner-portal.php';
require __DIR__.'/newsletter.php';
require __DIR__.'/password_change.php';
require __DIR__.'/account_security.php';
require __DIR__.'/confirm_password.php';
require __DIR__.'/cookies.php';
require __DIR__.'/sponsors.php';
require __DIR__.'/profile.php';
require __DIR__.'/mail_schedule.php';
require __DIR__.'/email_change.php';
require __DIR__.'/two_factor.php';
require __DIR__.'/totp.php';
require __DIR__.'/events_public.php';
require __DIR__.'/admin_events.php';
require __DIR__.'/sensitive.php';
require __DIR__.'/pole_proposals.php';
require __DIR__.'/account.php';
require __DIR__.'/v2.php';
require __DIR__.'/blog.php';





Route::get('/carte-info', [\App\Http\Controllers\CardPublicController::class, 'info'])

    ->name('card.info');



Route::get('/carte/verifier/{token}', [\App\Http\Controllers\CardPublicController::class, 'verify'])

    ->where('token', '[A-Za-z0-9]{40}')

    ->middleware('throttle:60,1')

    ->name('card.verify');



Route::middleware(['guest', 'throttle:20,1'])->group(function () {
    Route::get('/admin/invitation/{token}', [\App\Http\Controllers\Auth\InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/admin/invitation',[\App\Http\Controllers\Auth\InvitationController::class, 'store'])->name('invitation.store');
});

\Illuminate\Support\Facades\Route::get('/medias/{path}', [\App\Http\Controllers\MediaController::class, 'show'])->where('path', '.*')->name('media.show');





\Illuminate\Support\Facades\Route::prefix('chatbot')->name('chatbot.')->group(function () {

    \Illuminate\Support\Facades\Route::get('/init', [\App\Http\Controllers\ChatbotController::class, 'init'])->middleware('throttle:60,1')->name('init');

    \Illuminate\Support\Facades\Route::post('/message', [\App\Http\Controllers\ChatbotController::class, 'message'])->middleware('throttle:20,1')->name('message');

    \Illuminate\Support\Facades\Route::post('/avis', [\App\Http\Controllers\ChatbotController::class, 'feedback'])->middleware('throttle:30,1')->name('feedback');

    \Illuminate\Support\Facades\Route::post('/contact', [\App\Http\Controllers\ChatbotController::class, 'handoff'])->middleware('throttle:5,10')->name('handoff');

});
