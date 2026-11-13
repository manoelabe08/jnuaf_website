@extends('layouts.admin')



@section('title', $post->exists ? "Modifier l'article" : 'Nouvel article')



@section('content')



<div class="page-head"><h1>{{ $post->exists ? "Modifier l'article" : 'Nouvel article' }}</h1></div>



<form class="card" id="post-form" method="POST" enctype="multipart/form-data" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}">

  @csrf

  @if ($post->exists) @method('PUT') @endif

  <label>Titre<input type="text" name="title" value="{{ old('title', $post->title) }}" required></label>

  <label>Slug<input type="text" name="slug" value="{{ old('slug', $post->slug) }}"></label>

  <label>Extrait<textarea name="excerpt" rows="2">{{ old('excerpt', $post->excerpt) }}</textarea></label>

  <label>Image de couverture<input type="file" name="cover" accept="image/*"></label>

  <label>Cat&eacute;gorie<input type="text" name="category" value="{{ old('category', $post->category) }}"></label>

  <label>Tags (s&eacute;par&eacute;s par des virgules)<input type="text" name="tags" value="{{ old('tags', $post->tags) }}"></label>



  <div style="margin:12px 0 6px"><strong>Contenu</strong></div>

  <textarea name="content" id="content-field" rows="20" style="width:100%">{{ old('content', $post->content) }}</textarea>



  <button class="btn btn-green" type="submit" style="margin-top:14px">Enregistrer</button>

</form>



<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>

<script>

(function () {

  var uploadUrl = "{{ route('admin.posts.upload-image') }}";

  var token = document.querySelector("meta[name=csrf-token]").content;

  function upload(file, name) {

    var fd = new FormData();

    fd.append("image", file, name || file.name);

    return fetch(uploadUrl, {method:"POST", headers:{"X-CSRF-TOKEN":token, "Accept":"application/json"}, body:fd})

      .then(function (r) { return r.json(); })

      .then(function (d) { if (d && d.url) { return d.url; } throw new Error("Echec du televersement"); });

  }

  tinymce.init({

    selector: "#content-field",

    license_key: "gpl",

    promotion: false,

    branding: false,

    height: 560,

@if (file_exists(public_path('vendor/tinymce/langs/fr_FR.js')))

    language: "fr_FR",

    language_url: "{{ asset('vendor/tinymce/langs/fr_FR.js') }}",

@endif

    plugins: "lists link image table code preview fullscreen autolink charmap wordcount searchreplace",

    menubar: "edit view insert format table tools",

    toolbar: "undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | blockquote hr charmap | removeformat | preview fullscreen code",

    object_resizing: "img",

    resize_img_proportional: true,

    image_dimensions: true,

    image_description: true,

    image_uploadtab: true,

    automatic_uploads: true,

    paste_data_images: true,

    relative_urls: false,

    remove_script_host: false,

    convert_urls: false,

    images_upload_handler: function (blobInfo) { return upload(blobInfo.blob(), blobInfo.filename()); },

    file_picker_types: "image",

    file_picker_callback: function (cb, value, meta) {

      if (meta.filetype !== "image") { return; }

      var input = document.createElement("input");

      input.type = "file"; input.accept = "image/*";

      input.onchange = function () {

        var f = input.files[0]; if (!f) { return; }

        upload(f).then(function (url) { cb(url, {alt: f.name.replace(/\.[^.]+$/, "")}); });

      };

      input.click();

    },

    content_style: "body{font-family:Arial,sans-serif;font-size:16px;line-height:1.6}img{max-width:100%;height:auto}"

  });

})();

</script><!-- jnuaf-s102 -->



@endsection

