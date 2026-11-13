
<style>

.jmail{--g:#206c39;--o:#f5790a;--t:#193125;--m:#63756b;--b:#dde8e0;box-sizing:border-box;width:100%;padding:clamp(18px,3vw,36px);border-radius:22px;background:#f3f7f4;color:var(--t)}

.jmail *{box-sizing:border-box}

.jmail .jm-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;margin-bottom:26px}

.jmail .jm-eyebrow{margin:0 0 8px;color:var(--g);font-size:11px;font-weight:800;letter-spacing:1.8px;text-transform:uppercase}

.jmail .jm-title{margin:0;font-size:clamp(26px,3vw,36px);font-weight:800;color:var(--t)}

.jmail .jm-subtitle{margin:10px 0 0;color:var(--m);font-size:14px}

.jmail .jm-account{display:inline-flex;align-items:center;gap:9px;padding:11px 15px;border:1px solid var(--b);border-radius:999px;background:#fff;color:var(--g);font-size:13px;font-weight:700}

.jmail .jm-dot{width:8px;height:8px;border-radius:50%;background:var(--g)}

.jmail .jm-card{overflow:hidden;border:1px solid var(--b);border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(25,49,37,.05)}

.jmail .jm-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:18px;padding:22px;border-bottom:1px solid var(--b)}

.jmail .jm-heading-row{display:flex;align-items:center;gap:10px}

.jmail .jm-heading{margin:0;font-size:18px;font-weight:800;color:var(--t)}

.jmail .jm-count{padding:4px 8px;border-radius:8px;background:#eaf5ed;color:var(--g);font-size:12px;font-weight:800}

.jmail .jm-caption{margin:6px 0 0;color:var(--m);font-size:12px}

.jmail .jm-actions{display:flex;flex-wrap:wrap;gap:10px}

.jmail .jm-refresh,.jmail .jm-compose{display:inline-flex;align-items:center;min-height:42px;padding:10px 15px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none}

.jmail .jm-refresh{border:1px solid var(--b);background:#fff;color:var(--g)}

.jmail .jm-refresh:hover{background:#edf6ef}

.jmail .jm-compose{border:1px solid #efcba8;background:#fff4e8;color:#85501d;cursor:not-allowed}

.jmail .jm-message{display:grid;grid-template-columns:46px minmax(0,1fr);gap:15px;padding:23px;border-bottom:1px solid #edf2ee}

.jmail .jm-message:hover{background:#fafcfb}

.jmail .jm-avatar{display:flex;align-items:center;justify-content:center;width:46px;height:46px;border:1px solid #d7e8dc;border-radius:14px;background:#edf6ef;color:var(--g);font-size:18px;font-weight:800}

.jmail .jm-message-top{display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:6px 18px}

.jmail .jm-sender{margin:0;font-size:14px;font-weight:800;color:var(--t)}

.jmail .jm-date,.jmail .jm-email{color:var(--m);font-size:12px}

.jmail .jm-email{margin:4px 0 0}

.jmail .jm-subject{margin:13px 0 0;color:#293d31;font-size:14px;line-height:1.6}

.jmail .jm-attachment{display:inline-block;margin-top:10px;padding:4px 8px;border-radius:6px;background:#f0f3f1;color:#52645a;font-size:11px}

.jmail .jm-footer{padding:15px 22px;border-top:1px solid var(--b);background:#fafcfb;color:var(--m);font-size:12px}

.jmail .jm-notice{margin-top:16px;padding:14px 17px;border-left:3px solid var(--o);border-radius:0 10px 10px 0;background:#fff;color:#58695f;font-size:12px;line-height:1.7}

.jmail .jm-error{margin-bottom:18px;padding:15px;border:1px solid #efc7c7;border-radius:12px;background:#fff1f1;color:#922e2e;font-size:14px}

.jmail .jm-empty{padding:45px 22px;color:var(--m);text-align:center}

@media (max-width:640px){.jmail{padding:16px;border-radius:14px}.jmail .jm-toolbar,.jmail .jm-message{padding:17px}.jmail .jm-message{grid-template-columns:36px minmax(0,1fr);gap:11px}.jmail .jm-avatar{width:36px;height:36px;font-size:15px}}

.jmail .jm-compose-on{border:1px solid var(--g);background:var(--g);color:#fff;cursor:pointer}.jmail .jm-compose-on:hover{background:#185529}.jmail .jm-reply{display:inline-block;margin-top:10px;margin-right:10px;color:var(--g);font-size:12px;font-weight:700;text-decoration:none}.jmail .jm-reply:hover{text-decoration:underline}.jmail .jm-ok{margin-bottom:18px;padding:15px;border:1px solid #bfe0c9;border-radius:12px;background:#eaf5ed;color:#17542b;font-size:14px}.jmail .jm-form{padding:24px}.jmail .jm-label{display:block;margin:16px 0 6px;color:var(--t);font-size:13px;font-weight:700}.jmail .jm-input{width:100%;padding:12px 14px;border:1px solid var(--b);border-radius:10px;background:#fff;color:var(--t);font-family:inherit;font-size:14px}.jmail .jm-input[readonly]{background:#f3f7f4;color:var(--m)}.jmail .jm-input:focus{outline:3px solid rgba(32,108,57,.35);border-color:var(--g)}.jmail .jm-textarea{min-height:260px;resize:vertical;line-height:1.6}.jmail .jm-form-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:22px}.jmail .jm-send{min-height:42px;padding:10px 22px;border:0;border-radius:10px;background:var(--g);color:#fff;font-family:inherit;font-size:14px;font-weight:700;cursor:pointer}.jmail .jm-send:hover{background:#185529}

.jmail .jm-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}

.jmail .jm-tab{padding:10px 16px;border:1px solid var(--b);border-radius:999px;background:#fff;color:var(--g);font-size:13px;font-weight:700;text-decoration:none}

.jmail .jm-tab:hover{background:#edf6ef}

.jmail .jm-tab-on{border-color:var(--g);background:var(--g);color:#fff}

.jmail .jm-tab-on:hover{background:#185529}

.jmail .jm-hint{margin:6px 0 0;color:var(--m);font-size:12px}

.jmail .jm-filters{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:16px}.jmail .jm-search{flex:1 1 260px;width:auto}.jmail .jm-select{flex:0 1 190px;width:auto}.jmail .jm-unread{background:#f6fbf7}.jmail .jm-unread .jm-sender,.jmail .jm-unread .jm-subject{font-weight:800}.jmail .jm-new{display:inline-block;margin-left:8px;padding:2px 7px;border-radius:6px;background:#fff4e8;color:#85501d;font-size:10px;font-weight:800;vertical-align:middle}.jmail .jm-more{display:inline-block;margin-left:6px;padding:2px 7px;border-radius:6px;background:#eaf5ed;color:var(--g);font-size:11px;font-weight:800}.jmail .jm-pager{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:14px;padding:16px;border-top:1px solid var(--b)}.jmail .jm-details{margin-top:14px;padding:12px 14px;border:1px dashed var(--b);border-radius:10px}.jmail .jm-details summary{cursor:pointer;color:var(--g);font-size:13px;font-weight:700}.jmail .jm-check{display:flex;align-items:flex-start;gap:10px;margin-top:16px;color:var(--t);font-size:13px;line-height:1.5}.jmail .jm-check input{margin-top:3px;flex:0 0 auto}
.jmail .jm-open{color:inherit;text-decoration:none}.jmail .jm-open:hover{color:var(--g);text-decoration:underline}.jmail .jm-back{display:inline-block;margin-bottom:16px;color:var(--g);font-size:13px;font-weight:700;text-decoration:none}.jmail .jm-back:hover{text-decoration:underline}.jmail .jm-read{padding:clamp(18px,3vw,32px)}.jmail .jm-read-title{font-size:clamp(21px,2.5vw,28px);overflow-wrap:anywhere}.jmail .jm-meta{display:grid;grid-template-columns:70px minmax(0,1fr);gap:8px 14px;margin:20px 0;padding:16px;border-radius:12px;background:#f6faf7;font-size:13px}.jmail .jm-meta dt{color:var(--m);font-weight:700}.jmail .jm-meta dd{margin:0;overflow-wrap:anywhere}.jmail .jm-body{padding:6px 2px 18px;font-size:15px;line-height:1.75;overflow-wrap:anywhere}.jmail .jm-files{margin-top:8px;padding:16px;border:1px solid var(--b);border-radius:12px;background:#fafcfb}.jmail .jm-files-title{margin:0 0 10px;font-size:13px;font-weight:800}.jmail .jm-file{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;margin-top:6px;border:1px solid var(--b);border-radius:10px;background:#fff;color:var(--g);font-size:13px;font-weight:700;text-decoration:none;overflow-wrap:anywhere}.jmail .jm-file:hover{background:#edf6ef}.jmail .jm-file small{color:var(--m);font-weight:400;white-space:nowrap}
.jmail .jm-frame{display:block;width:100%;height:70vh;min-height:360px;border:1px solid var(--b);border-radius:12px;background:#fff}.jmail .jm-viewtabs{display:flex;gap:8px;margin-bottom:10px}.jmail .jm-viewtabs a{padding:6px 12px;border:1px solid var(--b);border-radius:999px;color:var(--g);font-size:12px;font-weight:700;text-decoration:none}.jmail .jm-viewtabs a.on{background:var(--g);border-color:var(--g);color:#fff}
</style>

