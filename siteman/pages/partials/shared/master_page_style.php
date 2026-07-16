<style>
    /* Shared master-page skin: follows Data Rombel / Data Ekstrakurikuler. */
    .sds-master-page{padding:0}
    .sds-master-page .sds-hero{background:#fff;border:1px solid #dee2e6;border-radius:0;padding:1rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:0;box-shadow:none}
    .sds-master-page .sds-hero h2{margin:0 0 .25rem;font-size:1.25rem;font-weight:600;color:#334151}
    .sds-master-page .sds-hero p{margin:0;color:#6c757d;font-size:.875rem}
    .sds-master-page .sds-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end}
    .sds-master-page .sds-card,.sds-master-page .sds-stat-card{background:#fff;border:1px solid #dee2e6;border-radius:0;box-shadow:none}
    .sds-master-page .sds-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;margin-bottom:0}
    .sds-master-page .sds-stats.three{grid-template-columns:repeat(3,minmax(0,1fr))}
    .sds-master-page .sds-stats.two{grid-template-columns:repeat(2,minmax(0,1fr))}
    .sds-master-page .sds-stat-card{padding:1rem;min-height:104px}
    .sds-master-page .sds-stat-card small{display:block;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.04em;font-size:.72rem}
    .sds-master-page .sds-stat-card strong{display:block;font-size:1.55rem;line-height:1.1;margin-top:.25rem;color:#212529;font-weight:700}
    .sds-master-page .sds-stat-card span{display:block;color:#6c757d;font-size:.78rem;margin-top:.25rem}
    .sds-master-page .sds-card-header{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.9rem 1rem;border-bottom:1px solid #dee2e6;background:#f8f9fa;flex-wrap:wrap}
    .sds-master-page .sds-card-header h5{margin:0;font-weight:600;color:#334151;font-size:1rem}
    .sds-master-page .sds-card-body{padding:1rem}
    .sds-master-page .sds-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:.75rem}
    .sds-master-page .sds-toolbar-left,.sds-master-page .sds-toolbar-right{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
    .sds-master-page .sds-toolbar .form-select,.sds-master-page .sds-toolbar .form-control{min-height:31px}
    .sds-master-page .sds-search{width:min(280px,100%)}
    .sds-master-page .sds-table-wrap{overflow:auto}
    .sds-master-page .sds-table{width:100%;border-collapse:collapse;background:#fff;min-width:760px;border:1px solid #eee}
    .sds-master-page .sds-table.wide{min-width:980px}
    .sds-master-page .sds-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;background:#f8f9fa;padding:.55rem .65rem;border-bottom:1px solid #dee2e6;white-space:nowrap}
    .sds-master-page .sds-table td{padding:.48rem .65rem;border-bottom:1px solid #edf1f5;vertical-align:middle;color:#334151}
    .sds-master-page .sds-table tr:last-child td{border-bottom:0}
    .sds-master-page .sds-table tbody tr:hover{background:#fbfcfd}
    .sds-master-page .sds-mini{font-size:.78rem;color:#6c757d}
    .sds-master-page .sds-code{display:inline-flex;align-items:center;border:1px solid #dee2e6;background:#f8f9fa;border-radius:.25rem;padding:.25rem .45rem;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;font-size:.78rem;color:#334151;white-space:nowrap}
    .sds-master-page .sds-badge{display:inline-flex;align-items:center;gap:5px;border-radius:.25rem;padding:.35rem .5rem;font-size:.75rem;font-weight:600;white-space:nowrap}
    .sds-master-page .sds-badge.ok{background:#d1e7dd;color:#0f5132}
    .sds-master-page .sds-badge.warn{background:#fff3cd;color:#664d03}
    .sds-master-page .sds-badge.info{background:#cff4fc;color:#055160}
    .sds-master-page .sds-badge.danger{background:#f8d7da;color:#842029}
    .sds-master-page .sds-badge.muted{background:#e9ecef;color:#495057}
    .sds-master-page .sds-actions{display:flex;align-items:center;gap:.35rem;justify-content:flex-end;white-space:nowrap;flex-wrap:wrap}
    .sds-master-page .sds-actions .btn{padding:.28rem .55rem;font-size:.78rem}
    .sds-master-page .alert{margin:1rem 1rem 0}
    .sds-master-page .sds-empty{padding:2rem 1rem!important;text-align:center;color:#6c757d!important}
    .sds-master-page .sds-section-gap{margin-top:1rem}
    .sds-master-page .sds-app-list{display:flex;gap:.35rem;flex-wrap:wrap}
    .sds-master-page .sds-app-list .sds-badge{font-weight:500}
    .sds-master-modal .modal-content{border-radius:0;border:1px solid #dee2e6}
    .sds-master-modal .modal-header{background:#f8f9fa;border-bottom:1px solid #dee2e6}
    .sds-master-modal .modal-footer{background:#f8f9fa;border-top:1px solid #dee2e6}
    .sds-master-modal .form-label{font-weight:600;color:#495057;font-size:.875rem}
    @media(max-width:1200px){
        .sds-master-page .sds-stats,.sds-master-page .sds-stats.three,.sds-master-page .sds-stats.two{grid-template-columns:repeat(2,1fr)}
        .sds-master-page .sds-hero{display:block}
        .sds-master-page .sds-hero-actions{justify-content:flex-start;margin-top:.75rem}
    }
    @media(max-width:700px){
        .sds-master-page{padding:0 6px}
        .sds-master-page .sds-stats,.sds-master-page .sds-stats.three,.sds-master-page .sds-stats.two{grid-template-columns:1fr}
        .sds-master-page .sds-toolbar,.sds-master-page .sds-toolbar-left,.sds-master-page .sds-toolbar-right{display:block;width:100%}
        .sds-master-page .sds-toolbar .form-select,.sds-master-page .sds-toolbar .form-control,.sds-master-page .sds-toolbar .btn,.sds-master-page .sds-hero-actions .btn{width:100%;margin-top:.5rem}
        .sds-master-page .sds-search{width:100%}
    }
</style>
