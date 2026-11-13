<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerDocument;
use App\Models\PartnerShare;
use App\Services\PartnerPortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerPortalController extends Controller
{
    public function index(PartnerPortalService $portal)
    {
        $storageError = null;
        try { $portal->root(); } catch (\Throwable $e) { $storageError = $e->getMessage(); }
        return view('admin.partner-portal.index', [
            'documents' => PartnerDocument::latest()->get(),
            'shares' => PartnerShare::with('documents')->latest()->paginate(20),
            'storageError' => $storageError,
            'pendingNotices' => DB::table('partner_notices')->whereNull('sent_at')->count(),
        ]);
    }

    public function upload(Request $request, PartnerPortalService $portal)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('partner_portal.max_upload_kb')],
        ]);
        try { $root = $portal->root(); } catch (\Throwable $e) {
            return back()->withErrors(['pdf' => $e->getMessage()]);
        }
        $file = $request->file('pdf');
        $handle = fopen($file->getRealPath(), 'rb');
        $header = fread($handle, 5); fclose($handle);
        if ($header !== '%PDF-') { return back()->withErrors(['pdf' => 'Le fichier ne commence pas par un en-tête PDF reconnu.']); }
        $stored = bin2hex(random_bytes(24)).'.pdf';
        $size = $file->getSize();
        $name = \Illuminate\Support\Str::slug($data['title']) ?: 'document';
        $file->move($root, $stored);
        @chmod($root.DIRECTORY_SEPARATOR.$stored, 0600);
        try {
            PartnerDocument::create([
                'title' => $data['title'], 'description' => $data['description'] ?? null,
                'stored_name' => $stored, 'download_name' => substr($name, 0, 150).'.pdf',
                'bytes' => $size, 'active' => true,
            ]);
        } catch (\Throwable $e) {
            @unlink($root.DIRECTORY_SEPARATOR.$stored); throw $e;
        }
        return back()->with('status', 'PDF ajouté au stockage privé. Associez-le aux espaces concernés.');
    }

    public function toggle(PartnerDocument $document)
    {
        $document->update(['active' => ! $document->active]);
        return back()->with('status', 'État du document modifié.');
    }

    public function preview(PartnerDocument $document, PartnerPortalService $portal)
    {
        return response()->file($portal->path($document), [
            'Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function store(Request $request, PartnerPortalService $portal)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'],
            'expires_at' => ['required', 'date', 'after:now'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['required', 'integer', 'distinct', 'exists:partner_documents,id'],
        ]);
        $email = $portal->normalize($data['email']);
        if (! $portal->knownEmail($email)) {
            throw ValidationException::withMessages(['email' => 'Adresse absente de users et des inscriptions non anonymisées.']);
        }
        $share = DB::transaction(function () use ($data, $email) {
            $share = PartnerShare::create([
                'name' => $data['name'], 'email' => $email, 'expires_at' => $data['expires_at'], 'status' => 'draft',
            ]);
            $share->documents()->sync($data['documents']);
            return $share;
        });
        return redirect()->route('admin.partner-portal.show', $share)
            ->with('status', 'Espace créé. Vérifiez les documents puis cliquez sur « Générer et envoyer le lien ».');
    }

    public function show(PartnerShare $share)
    {
        return view('admin.partner-portal.show', [
            'share' => $share->load('documents'), 'documents' => PartnerDocument::latest()->get(),
            'events' => DB::table('partner_events')->leftJoin('partner_documents', 'partner_documents.id', '=', 'partner_events.partner_document_id')
                ->where('partner_share_id', $share->id)->orderByDesc('partner_events.id')
                ->select('partner_events.*', 'partner_documents.title as document_title')->paginate(40),
        ]);
    }

    public function update(Request $request, PartnerShare $share, PartnerPortalService $portal)
    {
        $data = $request->validate([
            'expires_at' => ['required', 'date', 'after:now'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['integer', 'distinct', 'exists:partner_documents,id'],
        ]);
        DB::transaction(function () use ($share, $data, $portal) {
            $share = PartnerShare::lockForUpdate()->findOrFail($share->id);
            $share->update(['expires_at' => $data['expires_at']]);
            $share->documents()->sync($data['documents'] ?? []);
            $portal->event($share, 'documents_ou_échéance_modifiés');
        });
        return back()->with('status', 'Documents et date de fin enregistrés. Un lien invalidé reste invalidé tant qu’un nouveau lien n’est pas généré.');
    }

    public function invite(PartnerShare $share, PartnerPortalService $portal)
    {
        try { $portal->sendInvitation($share); }
        catch (ValidationException $e) { throw $e; }
        catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['mail' => 'Échec de l’envoi. Consultez le journal Laravel et la configuration email avant de réessayer.']);
        }
        return back()->with('status', 'Nouveau lien transmis au transport email. L’ancien lien et ses sessions ne sont plus valables.');
    }

    public function revoke(PartnerShare $share, PartnerPortalService $portal)
    {
        DB::transaction(function () use ($share, $portal) {
            $share = PartnerShare::lockForUpdate()->findOrFail($share->id);
            $portal->revoke($share, 'Révocation par le comité d’organisation');
        });
        return back()->with('status', 'Lien révoqué. Les notifications seront traitées par la tâche planifiée.');
    }
}
