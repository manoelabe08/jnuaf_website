
(function () {

  'use strict';



  var dialog = null;

  var currentHref = '';

  var currentNumber = '';



  function build() {

    dialog = document.createElement('dialog');

    dialog.className = 'call-dialog';

    dialog.setAttribute('aria-labelledby', 'call-title');

    dialog.innerHTML =

      '<form method="dialog">' +

      '<h2 id="call-title">Lancer un appel ?</h2>' +

      '<p>Vous êtes sur le point d’appeler <strong class="call-number"></strong>. ' +

      'Des frais peuvent s’appliquer selon votre opérateur.</p>' +

      '<div class="call-actions">' +

      '<button type="submit" value="cancel" class="call-cancel" autofocus>Annuler</button>' +

      '<button type="submit" value="copy" class="call-copy">Copier le numéro</button>' +

      '<button type="submit" value="ok" class="call-ok">Appeler</button>' +

      '</div></form>';

    document.body.appendChild(dialog);



    dialog.addEventListener('click', function (event) {

      if (event.target === dialog) {

        dialog.close('cancel');

      }

    });



    dialog.addEventListener('close', function () {

      if (dialog.returnValue === 'ok' && /^tel:\+?[0-9]+$/.test(currentHref)) {

        window.location.href = currentHref;

      } else if (dialog.returnValue === 'copy' && navigator.clipboard) {

        navigator.clipboard.writeText(currentNumber).catch(function () {});

      }

    });

  }



  document.addEventListener('click', function (event) {

    var link = event.target.closest ? event.target.closest('a[data-call]') : null;



    if (!link) {

      return;

    }



    event.preventDefault();

    currentHref = link.getAttribute('href') || '';

    currentNumber = link.getAttribute('data-call') || link.textContent.trim();



    if (typeof HTMLDialogElement !== 'function') {

      if (window.confirm('Vous êtes sur le point d’appeler ' + currentNumber + '. Continuer ?')) {

        if (/^tel:\+?[0-9]+$/.test(currentHref)) {

          window.location.href = currentHref;

        }

      }

      return;

    }



    if (!dialog) {

      build();

    }



    dialog.querySelector('.call-number').textContent = currentNumber;

    dialog.returnValue = '';

    dialog.showModal();

  });

})();

