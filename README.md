# GrayShop katalog

Lightweight PHP katalog bez košarice, s administracijom "Kontrolna soba" i pohranom podataka u JSON datotekama.

## Značajke
- Mobilno prvo sučelje s responzivnim dizajnom, animacijama i pristupačnim svjetlosnim efektima.
- Filtriranje po kategorijama i sortiranje po cijeni ili datumu.
- Administracija s prijavom preko lozinke, CSRF zaštitom i uređivanjem fotografija (redoslijed, dodavanje, brisanje).
- Obrada slika preko GD-a: rezanje/centriranje, JPEG 85 %, min 500×500 px, generiranje minijatura.
- Pohrana u `data/items/{id}.json` i indeks `data/products.json` za brzi prikaz.
- Skripta `rebuild_index.php` za rekonstrukciju indeksa.

## Zahtjevi
- PHP 8.0 ili noviji
- Apache s omogućenim `.htaccess`
- PHP ekstenzije: `gd`, `fileinfo`, `mbstring`
- Omogućeni `finfo` i `session` moduli

## Lokalno pokretanje
1. Provjerite da su instalirani PHP 8+ i ekstenzije `gd`, `fileinfo`, `mbstring`.
2. U korijenu projekta pokrenite ugrađeni PHP poslužitelj (poslužuje `/public` direktorij):
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
3. Otvorite `http://127.0.0.1:8000/` za javni katalog i `http://127.0.0.1:8000/control-room/` za administraciju.
4. Za prijavu koristite zadanu lozinku `SurveillanceRoom` ili postavite novu prema uputama niže.

## Instalacija na Hostinger/Apache
1. Prijavite se na kontrolni panel i postavite root direktorij web stranice na `/public` ako je moguće. Ako to nije izvedivo, pristup frontendu je preko `public/index.php`, a admin sučelju preko `/control-room/`.
2. Prenesite sve datoteke iz repozitorija na poslužitelj, zadržavajući strukturu mapa.
3. Provjerite dozvole direktorija:
   - `data/` i `uploads/` moraju biti zapisivi za PHP proces (`chmod 775` ili `chmod 770` ovisno o konfiguraciji).
4. U `includes/config.php` ostaje zadani hash lozinke (za lozinku `SurveillanceRoom`). Ako želite prilagodbu:
   ```bash
   php -r "echo password_hash('novaLozinka', PASSWORD_DEFAULT);"
   ```
   Zamijenite vrijednost `admin_password_hash` novim hashom.
5. Apache `.htaccess` datoteka u rootu postavlja `X-Robots-Tag` na `noindex`. Dodatne `.htaccess` datoteke u `data/` i `uploads/` onemogućuju listing i direktan pristup datotekama.
6. Aktivirajte HTTPS certifikat (npr. Let’s Encrypt) i postavite preusmjeravanje na HTTPS. Admin dio odbacuje HTTP zahtjeve.
7. Po završetku prijenosa pokrenite rebuild indeksa za provjeru konzistentnosti:
   ```bash
   php rebuild_index.php
   ```
   Pokretanje nije obavezno, ali se preporučuje nakon migracije sadržaja.

## Administracija
- Prijava se nalazi na `/control-room/login.php`.
- Lozinka prema zadanom: `SurveillanceRoom` (zamijenite hash prema potrebi).
- Sve POST forme koriste CSRF token. Sesije koriste `httponly`, `secure` i `samesite=strict` kolačiće.
- Upozorenje se prikazuje kada broj artikala premaši 60.

## Rad s fotografijama
- Pri dodavanju/uređivanju učitajte 1–3 datoteke (`jpg`, `jpeg`, `png`).
- Slike se spremaju u `uploads/{id}/photo_xxx.jpg` uz minijaturu `thumb_xxx.jpg`.
- Prvi element u popisu fotografija je naslovna fotografija.

## Rekonstrukcija indeksa
Ako dođe do oštećenja `data/products.json`:
```bash
php rebuild_index.php
```
Skripta prolazi kroz `data/items/*.json`, regenerira indeks i zamjenjuje postojeću datoteku koristeći atomsko pisanje.

## Oporavak nakon grešaka pisanja
- Svako spremanje koristi privremene datoteke, `flock` i `rename`. Ako dođe do prekida, provjerite postoji li `.lock` datoteka i eventualno je uklonite.
- Nakon neuspješnog spremanja novog artikla provjerite postoji li prazna mapa unutar `uploads/`; ako postoji, možete je ručno obrisati.

## Dodatne napomene
- Timezone: `Europe/Zagreb`.
- Format cijena: `1.234,56 €`.
- Robots.txt blokira indeksaciju (`Disallow: /`).
- Sve korisnički vidljive poruke su na hrvatskom jeziku; kod i komentari na engleskom.
