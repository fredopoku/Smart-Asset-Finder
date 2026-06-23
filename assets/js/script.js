function start_loader() {
    $('body').append('<div id="preloader"><div class="loader-holder"><div></div><div></div><div></div><div></div>')
}

function end_loader() {
    $('#preloader').fadeOut('fast', function() {
        $('#preloader').remove();
    })
}
// Toast container — injected once into body
function _safToastContainer() {
    if (!$('#saf-toast-wrap').length) {
        $('body').append('<div id="saf-toast-wrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:99999"></div>');
    }
}

window.alert_toast = function(msg, type, duration) {
    type     = type     || 'success';
    duration = duration || 4500;

    var icons = {
        success: 'bi-check-circle-fill',
        danger:  'bi-x-circle-fill',
        error:   'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info:    'bi-info-circle-fill'
    };
    var bgs = {
        success: 'text-bg-success',
        danger:  'text-bg-danger',
        error:   'text-bg-danger',
        warning: 'text-bg-warning',
        info:    'text-bg-info'
    };

    var bg   = bgs[type]   || 'text-bg-success';
    var icon = icons[type] || 'bi-check-circle-fill';
    var id   = 'saf-toast-' + Date.now();

    _safToastContainer();

    $('#saf-toast-wrap').append(
        '<div id="'+id+'" class="toast align-items-center '+bg+' border-0 shadow-sm mb-1" role="alert" aria-live="assertive" aria-atomic="true">'
      + '  <div class="d-flex">'
      + '    <div class="toast-body d-flex align-items-center gap-2" style="font-size:.88rem">'
      + '      <i class="bi '+icon+' flex-shrink-0"></i><span>'+msg+'</span>'
      + '    </div>'
      + '    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>'
      + '  </div>'
      + '</div>'
    );

    var el    = document.getElementById(id);
    var toast = new bootstrap.Toast(el, { delay: duration });
    toast.show();
    el.addEventListener('hidden.bs.toast', function(){ $(el).remove(); });

    // Backward-compat: keep #msg-container updated for admin pages that rely on it
    if ($('#msg-container').length) {
        var cls = (type === 'error') ? 'danger' : type;
        $('#msg-container').html('<div class="alert alert-'+cls+' rounded-0 mb-0 py-2" style="font-size:.88rem">'+msg+'</div>');
    }
}

// Alias
window.notify = window.alert_toast;

// Modal alert — replaces native alert()
window._alert = function(msg, type, title) {
    type  = type  || 'info';
    title = title || '';
    var icons = {
        success: '<i class="bi bi-check-circle-fill" style="color:#10b981"></i>',
        error:   '<i class="bi bi-x-circle-fill"     style="color:#ef4444"></i>',
        warning: '<i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b"></i>',
        info:    '<i class="bi bi-info-circle-fill"  style="color:#4f46e5"></i>'
    };
    $('#saf_alert_icon').html(icons[type] || icons.info);
    $('#saf_alert_msg').html(msg);
    if(title) {
        $('#saf_alert_modal .modal-body').prepend('<div class="fw-bold mb-2" style="font-size:.95rem">'+title+'</div>');
    }
    var m = new bootstrap.Modal(document.getElementById('saf_alert_modal'));
    m.show();
};

// Modal prompt — replaces native prompt(), result via callback
window._prompt = function(title, placeholder, currentValue, callback) {
    $('#saf_prompt_title').text(title || '');
    $('#saf_prompt_input').val(currentValue || '').attr('placeholder', placeholder || '');
    $('#saf_prompt_hint').text('');
    var modal = document.getElementById('saf_prompt_modal');
    var m = new bootstrap.Modal(modal);
    m.show();
    // Focus input after modal opens
    modal.addEventListener('shown.bs.modal', function focus(){ $('#saf_prompt_input').focus(); modal.removeEventListener('shown.bs.modal', focus); });
    // OK button
    $('#saf_prompt_ok').off('click').on('click', function(){
        var val = $('#saf_prompt_input').val().trim();
        m.hide();
        if(typeof callback === 'function') callback(val);
    });
    // Enter key submits
    $('#saf_prompt_input').off('keydown.prompt').on('keydown.prompt', function(e){
        if(e.key === 'Enter'){ $('#saf_prompt_ok').trigger('click'); }
    });
    // Cancel clears handler
    modal.addEventListener('hidden.bs.modal', function clear(){
        $('#saf_prompt_input').off('keydown.prompt');
        modal.removeEventListener('hidden.bs.modal', clear);
    });
};

$(document).ready(function() {
    // Login
    $('#login-frm').submit(function(e) {
            e.preventDefault()
            start_loader()
            if ($('.err_msg').length > 0)
                $('.err_msg').remove()
            $.ajax({
                url: _base_url_ + 'classes/Login.php?f=login',
                method: 'POST',
                data: $(this).serialize(),
                error: err => {
                    console.log(err)

                },
                success: function(resp) {
                    if (resp) {
                        resp = JSON.parse(resp)
                        if (resp.status == 'success') {
                            location.replace(_base_url_ + 'admin');
                        } else if (resp.status == 'incorrect') {
                            var _frm = $('#login-frm')
                            var _msg = "<div class='alert alert-danger err_msg'><i class='fa fa-exclamation-triangle'></i> Incorrect username or password</div>"
                            _frm.prepend(_msg)
                            _frm.find('input').addClass('is-invalid')
                            $('[name="username"]').focus()
                        }
                        end_loader()
                    }
                }
            })
        })
        //Establishment Login
    $('#flogin-frm').submit(function(e) {
        e.preventDefault()
        start_loader()
        if ($('.err_msg').length > 0)
            $('.err_msg').remove()
        $.ajax({
            url: _base_url_ + 'classes/Login.php?f=flogin',
            method: 'POST',
            data: $(this).serialize(),
            error: err => {
                console.log(err)

            },
            success: function(resp) {
                if (resp) {
                    resp = JSON.parse(resp)
                    if (resp.status == 'success') {
                        location.replace(_base_url_ + 'faculty');
                    } else if (resp.status == 'incorrect') {
                        var _frm = $('#flogin-frm')
                        var _msg = "<div class='alert alert-danger text-white err_msg'><i class='fa fa-exclamation-triangle'></i> Incorrect username or password</div>"
                        _frm.prepend(_msg)
                        _frm.find('input').addClass('is-invalid')
                        $('[name="username"]').focus()
                    }
                    end_loader()
                }
            }
        })
    })

    //user login
    $('#slogin-frm').submit(function(e) {
            e.preventDefault()
            start_loader()
            if ($('.err_msg').length > 0)
                $('.err_msg').remove()
            $.ajax({
                url: _base_url_ + 'classes/Login.php?f=slogin',
                method: 'POST',
                data: $(this).serialize(),
                error: err => {
                    console.log(err)

                },
                success: function(resp) {
                    if (resp) {
                        resp = JSON.parse(resp)
                        if (resp.status == 'success') {
                            location.replace(_base_url_ + 'student');
                        } else if (resp.status == 'incorrect') {
                            var _frm = $('#slogin-frm')
                            var _msg = "<div class='alert alert-danger err_msg'><i class='fa fa-exclamation-triangle'></i> Incorrect username or password</div>"
                            _frm.prepend(_msg)
                            _frm.find('input').addClass('is-invalid')
                            $('[name="username"]').focus()
                        }
                        end_loader()
                    }
                }
            })
        })
        // System Info
    $('#system-frm').submit(function(e) {
        e.preventDefault()
        start_loader()
        if ($('.err_msg').length > 0)
            $('.err_msg').remove()
        $.ajax({
            url: _base_url_ + 'classes/SystemSettings.php?f=update_settings',
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            success: function(resp) {
                if (resp.status == 'success') {
                    // alert_toast("Data successfully saved",'success')
                    location.reload()
                } else if (resp.status == 'failed' && !!resp.msg) {
                    $('#msg').html('<div class="alert alert-danger err_msg">' + resp.msg + '</div>')
                    $("html, body").animate({ scrollTop: 0 }, "fast");
                } else {
                    $('#msg').html('<div class="alert alert-danger err_msg">An Error occured</div>')
                }
                end_loader()
            }
        })
    })
})
/* ── Global Scroll Reveal (public pages) ───────────── */
(function(){
  if(!('IntersectionObserver' in window)) return;
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(e.isIntersecting){
        e.target.classList.add('revealed');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -48px 0px' });

  function observe(){
    document.querySelectorAll('.scroll-reveal:not(.revealed)').forEach(function(el){
      io.observe(el);
    });
  }
  document.addEventListener('DOMContentLoaded', observe);
  // Re-observe after AJAX content loads (e.g. search results)
  window._revealObserve = observe;
})();

/* ── Animated stat counters ─────────────────────────── */
(function(){
  if(!('IntersectionObserver' in window)) return;
  var ran = false;
  var cio = new IntersectionObserver(function(entries){
    if(ran) return;
    entries.forEach(function(e){
      if(e.isIntersecting){
        ran = true;
        document.querySelectorAll('.stat-num,[data-count]').forEach(function(el){
          var raw = el.textContent.replace(/[^0-9]/g,'');
          var end = parseInt(raw);
          if(!end || end < 2) return;
          var dur  = 1400, start = performance.now();
          var orig = el.textContent;
          requestAnimationFrame(function tick(now){
            var p = Math.min((now - start) / dur, 1);
            var ease = 1 - Math.pow(1 - p, 3);
            var cur  = Math.round(ease * end);
            el.textContent = orig.replace(raw, cur.toLocaleString());
            if(p < 1) requestAnimationFrame(tick);
            else el.textContent = orig;
          });
        });
      }
    });
  }, { threshold: 0.3 });
  document.addEventListener('DOMContentLoaded', function(){
    var target = document.querySelector('.stats-strip,.hero-stats');
    if(target) cio.observe(target);
  });
})();

/* ── Magnetic button effect on .btn-gradient ────────── */
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.btn-gradient,.btn-claim-action').forEach(function(btn){
    btn.addEventListener('mousemove', function(e){
      var r = btn.getBoundingClientRect();
      var x = (e.clientX - r.left - r.width/2)  * .25;
      var y = (e.clientY - r.top  - r.height/2) * .25;
      btn.style.transform = 'translate('+x+'px,'+y+'px) translateY(-2px)';
    });
    btn.addEventListener('mouseleave', function(){
      btn.style.transform = '';
    });
  });
});
