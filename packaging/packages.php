<?php
return [
 'core'=>[
  'package'=>'sds-core','version'=>'3.0.0-dev','type'=>'overlay',
  'include'=>['app','assets','fonts','fpdf','siteman','vendor','modules/core/module.json','modules/enrollment','config/runtime.php','config/db.php','config/config.php','config/academic_year.php','config/form-fields.php','config/app.example.php','config/modules.example.php','install','tools','packaging/packages.php','packaging/schema-map.php','packaging/schema-products.php','uploads/.htaccess','storage/.htaccess','.htaccess','composer.json','composer.lock','db.php','config.php','index.php','router.php','INSTALL.md','PRODUCTION.md','VERSION.txt'],
  'exclude'=>['config/app.php','config/modules.php','config/perpus.php','config/anjungan_runtime.php','vendor/google/apiclient-services/**','install/schema/attendance.sql*','install/schema/canteen.sql*','install/schema/emoney.sql*','install/schema/library.sql*','install/schema/kiosk.sql*','install/schema/sarpras.sql*','install/seeds/library.sql','install/migrations/*perpus*','install/migrations/*kantin*','install/migrations/*emoney*','siteman/pages/anjungan_admin.php','siteman/pages/partials/anjungan/**']
 ],
 'attendance'=>['package'=>'sds-module-attendance','version'=>'1.0.0','type'=>'module-overlay','include'=>['modules/attendance'],'exclude'=>[]],
 'canteen'=>['package'=>'sds-module-canteen','version'=>'1.0.0','type'=>'module-overlay','include'=>['modules/canteen'],'exclude'=>[]],
 'emoney'=>['package'=>'sds-module-emoney','version'=>'1.0.0','type'=>'module-overlay','include'=>['modules/emoney'],'exclude'=>[]],
 'library'=>['package'=>'sds-module-library','version'=>'2.6.0','type'=>'module-overlay','include'=>['modules/library','config/perpus.php','MAPPING_PERPUSTAKAAN.md','CHANGELOG.md'],'exclude'=>[]],
 'kiosk'=>['package'=>'sds-module-kiosk','version'=>'1.0.0','type'=>'module-overlay','include'=>['modules/kiosk','config/anjungan_runtime.php','siteman/pages/anjungan_admin.php','siteman/pages/partials/anjungan'],'exclude'=>[]],
 'sarpras'=>['package'=>'sds-module-sarpras','version'=>'0.1.0-dev','type'=>'module-overlay','include'=>['modules/sarpras/module.json','install/schema/sarpras.sql','install/schema/sarpras.sql.sha256'],'exclude'=>[]],
];
