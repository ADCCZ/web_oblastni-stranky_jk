
[x] 1. Převod databáze z xamppu do Admineru
[x] 2. Umožnit vedoucímu, který vytvořil danou akci (nebo jakémukoliv adminovi), možnost akci upravit
[x] 3. Chybová stránka pro neznámý/nevalidní odkaz - vlastní SVG/CSS animace kompasu (Lottie animace na IconScoutu/LottieFiles byly placené/vyžadovaly účet)
[x] 4. Stránka "ještě se pracuje" pro odkazy z navbaru, kde není dodělaná stránka - vlastní SVG/CSS animace kladiva
    - mimochodem opraven starý bug: chybová stránka byla v produkci vždy rozbitá (500 misto 404) - Error4xxPresenter nedědil BasePresenter + relativní odkazy v navbaru/patičce selhávaly z modulu Error
[] 5. Najít využití pro teepee animaci
    https://iconscout.com/lottie-animations/teepee-tent
[] 6. Rozhodnout, co s .patch soubory v rootu (0001-fix-blockers-security..., 0001-phase-2_admin-registrations-gallery..., 0001-phase-3_children-registrations...) + jejich pathfinder-jk_*_changed-files/ složkami a NAVOD-nasazeni-zmen.md
    - popisují jiný, nikdy nesloučený plán (jiné migrace, GalleryRepository, PaymentService, admin modul...), aplikovat by teď konfliktovalo se současným kódem
    - buď smazat jako zastaralé, nebo si vybrat konkrétní části k dodělání a zbytek smazat
