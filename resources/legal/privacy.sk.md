# Ochrana osobných údajov

**Naposledy aktualizované: 22. augusta 2026**

## 1. Kto je zodpovedný

TáborPlanner („Služba“), dostupný na `tabory.barnas.net`, je bezplatný, nekomerčný
hobby projekt, ktorý prevádzkuje:

**Michal Barnas** — fyzická osoba, Slovenská republika
Kontakt: **michal@barnas.net**

Za Službou nestojí žiadna firma a neplynie z nej žiadny príjem.

Pre rozdelenie zodpovednosti to znamená:

- Pri **údajoch tvojho účtu** (časť 3.1) a **technických údajoch**, ktoré server
  zaznamenáva (časť 3.3), je Michal Barnas **prevádzkovateľom** podľa čl. 4 ods. 7 GDPR.
- Pri **všetkom, čo do aplikácie zadáš o iných ľuďoch** — mená vedúcich, zaradenie do
  skupín, e-mailové adresy v pozvánkach, narodeniny, poznámky zo spätnej väzby (časť 3.2)
  — **prevádzkovateľom si ty a organizácia, ktorá tábor robí.** Služba tento obsah iba
  ukladá a zobrazuje podľa tvojich pokynov a vystupuje ako **sprostredkovateľ**. Ty
  rozhoduješ, čo sa zadá, prečo a ako dlho to tam ostane. Pozri časť 4.

## 2. Rozsah

Tieto zásady sa týkajú výlučne webovej aplikácie na `tabory.barnas.net`. Netýkajú sa iných
stránok, na ktoré odkazuje, vrátane `barnas.net`.

## 3. Aké údaje sa spracúvajú

### 3.1 Údaje účtu

Získané pri registrácii a pri používaní bezpečnostných funkcií:

| Údaj                                                     | Načo                                                    |
| -------------------------------------------------------- | ------------------------------------------------------- |
| Meno                                                     | Vidia ho ostatní členovia tvojich táborov               |
| E-mailová adresa                                         | Prihlásenie, obnova hesla, pozvánky do tábora, overenie |
| Heslo (hašované, bcrypt)                                 | Overenie totožnosti — otvorený text sa nikdy neukladá   |
| Čas overenia e-mailu                                     | Aby bolo jasné, či je adresa potvrdená                  |
| Kľúč a záložné kódy dvojfaktorového overenia (šifrované) | Voliteľné dvojfaktorové overenie                        |
| Údaje passkey (verejný kľúč, ID poverenia)               | Voliteľné prihlásenie bez hesla                         |
| Token „zapamätať si ma“                                  | Necháva ťa prihláseného, ak si o to požiadaš            |
| Čas vytvorenia a úpravy účtu                             | Prevádzková evidencia                                   |

Voliteľne, ak sa rozhodneš používať AI súhrny:

| Údaj                                    | Načo                                                      |
| --------------------------------------- | --------------------------------------------------------- |
| Tvoj API kľúč Google Gemini (šifrovaný) | Aby sa súhrny generovali _tvojím_ kľúčom — pozri časť 6.3 |

### 3.2 Obsah, ktorý zadáš

Služba ukladá to, čo do nej vložíš ty a tvoji spoluorganizátori:

- Tábory: názov, rok, dátumy, popis, miesto, ikona a farba
- Dni tábora: dátum, meniny, **narodeniny**, materiál, poznámky a zhodnotenia
- Bloky a položky programu: názvy, popisy, časy, poznámky, materiál, zodpovedná osoba,
  stav splnenia a body
- Aktivity, databázy aktivít a kategórie vrátane zdieľaných
- **Mená vedúcich** (vedúci nemusí mať účet, aby bol v zozname), ich farby a zaradenie
  do skupín
- Skupiny tábora, typy skupín a ich bodovanie
- Pozvánky: **e-mailová adresa**, ktorú pozývaš, a token pozvánky
- Denné zhodnotenia: hviezdičkové hodnotenia, textové odôvodnenia, odpovede na vlastné
  otázky tábora a to, kto ich napísal
- Uložené verzie plánu a databázy (snímky vyššie uvedeného)
- AI súhrny vytvorené z vyššie uvedeného, ak túto funkciu použiješ

Časť týchto polí je voľný text. **Čokoľvek do nich napíšeš, sa uloží tak, ako to je** —
pozri časť 11 o zadávaní údajov o deťoch.

### 3.3 Technické údaje

Zaznamenávajú sa automaticky, ako na každom webovom serveri:

- **Logy servera**: IP adresa, dátum a čas, požadovaná URL, stavový kód HTTP, user agent
- **Záznamy relácií** (v databáze): identifikátor relácie, tvoja IP adresa, tvoj user agent
  a čas poslednej aktivity
- Chybové logy, ktoré môžu obsahovať vyššie uvedené, keď niečo zlyhá

## 4. Prevádzkovateľ a sprostredkovateľ, po ľudsky

Ak robíš tábor a zadáš mená svojich vedúcich, ich spätnú väzbu a e-mailové adresy, ktoré
pozývaš, **si to ty, kto sa rozhodol tieto údaje spracúvať.** Podľa GDPR si tým
prevádzkovateľom ty (alebo tvoja farnosť, občianske združenie či organizácia). Služba ti dá
nástroje; nerozhoduje o tom, čo zbieraš.

V praxi to znamená:

- Zodpovedáš za to, že máš právny základ pre ľudí, ktorých údaje zadávaš — obvykle ich
  súhlas alebo oprávnený záujem na organizovaní tábora.
- Mal by si týmto ľuďom povedať, že ich údaje sú v TáborPlanneri a kto ho prevádzkuje.
- Nesmieš zadávať údaje, na ktoré nemáš právo (časť 11).
- Prevádzkovateľ bude konať podľa tvojich pokynov: obsah tvojho tábora nepoužije na
  vlastné účely, nepredá ho a neposkytne ho nikomu okrem sprostredkovateľov v časti 6.

## 5. Právne základy a účely

| Účel                                                                                       | Právny základ (čl. 6 GDPR)                                                       |
| ------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------- |
| Vytvorenie a prevádzka účtu, prihlasovanie                                                 | 6 ods. 1 písm. b) — plnenie zmluvy s tebou                                       |
| Ukladanie a zobrazovanie obsahu tábora                                                     | 6 ods. 1 písm. b) a 6 ods. 1 písm. f) tam, kde ide o údaje iných členov tábora   |
| Odosielanie prevádzkových e-mailov (overenie, obnova hesla, pozvánky)                      | 6 ods. 1 písm. b)                                                                |
| Udržiavanie bezpečnosti: logy, relácie, obmedzovanie počtu pokusov, dvojfaktorové overenie | 6 ods. 1 písm. f) — oprávnený záujem na predchádzaní zneužitiu                   |
| Zapamätanie jazyka, motívu a stavu bočného panela                                          | 6 ods. 1 písm. f) — oprávnený záujem na funkčnom rozhraní a tvoja výslovná voľba |
| Odoslanie tvojho textu do Google Gemini na vytvorenie súhrnu                               | 6 ods. 1 písm. a) — tvoj súhlas, daný zadaním kľúča a stlačením tlačidla         |

Žiadne osobné údaje sa nepoužívajú na reklamu, profilovanie ani ďalší predaj. Nie je čo
predávať.

## 6. Kto ďalší údaje vidí

### 6.1 Hosting

Služba beží na jedinom virtuálnom serveri prenajatom od **Hetzner Online GmbH**
(Industriestr. 25, 91710 Gunzenhausen, Nemecko), v dátovom centre v Európskej únii.
Aplikácia, databáza aj zálohy sú na tejto infraštruktúre. Hetzner vystupuje ako
sprostredkovateľ.

### 6.2 Doručovanie e-mailov

Odchádzajúce e-maily (overenie, obnova hesla, pozvánky do tábora) sa odovzdávajú SMTP
službe nastavenej pre Službu, ktorá spracuje adresu príjemcu a obsah správy, aby ju
doručila.

### 6.3 Google Gemini — len ak si to zapneš

Funkcia AI súhrnov je **predvolene vypnutá a vyžaduje, aby si vložil vlastný API kľúč
Google Gemini.** Ak kľúč zadáš a požiadaš o súhrn, do Gemini API
(`generativelanguage.googleapis.com`) sa odošle:

- program tábora alebo dňa, ktorý sa sumarizuje — názvy aktivít, popisy a časy
- spätná väzba, ktorú napísali tvoji vedúci, vrátane textových odpovedí a odôvodnení

Ide o prenos do **Google LLC v Spojených štátoch** a riadi sa tvojou vlastnou zmluvou s
Googlom a podmienkami Google API — nie týmito zásadami. Keďže kľúč dodávaš ty, pre túto
požiadavku si zákazníkom Googlu ty.

**Ak nechceš, aby text tvojho tábora opúšťal EÚ, nezadávaj Gemini API kľúč.** Všetko
ostatné funguje aj bez neho. Uložený kľúč môžeš kedykoľvek zmazať v Nastavenia → AI, čím sa
okamžite odstráni z databázy.

### 6.4 Nikto iný

Nie je tu žiadna analytika, žiadne reklamné siete, žiadne sledovacie pixely, žiadne chatové
widgety ani žiadne CDN. Webové fonty sa zabalia do vlastných súborov Služby pri builde a
servírujú sa z jej vlastnej domény, takže tvoj prehliadač nekontaktuje žiadneho cudzieho
poskytovateľa fontov.

Údaje môžu byť poskytnuté aj tam, kde to vyžaduje zákon.

## 7. Prenosy mimo EÚ

Žiadne, okrem prípadu Gemini v časti 6.3, ktorý nastane iba vtedy, keď si ho vedome zapneš
vlastným kľúčom.

## 8. Ako dlho sa údaje uchovávajú

| Údaj                | Doba uchovávania                                                              |
| ------------------- | ----------------------------------------------------------------------------- |
| Údaje účtu          | Kým si nezmažeš účet                                                          |
| Obsah tábora        | Kým ho ty alebo iný člen tábora nezmaže, alebo kým sa nezmaže vlastniaci účet |
| Záznamy relácií     | Do vypršania (2 hodiny nečinnosti) alebo do odhlásenia                        |
| Logy servera a chýb | Rotujú sa serverom; obvykle niekoľko týždňov                                  |
| Zálohy              | Prepisujú sa v zálohovacom cykle; zmazania sa premietnu, ako sa zálohy rotujú |

Zmazaním účtu sa tvoj používateľský záznam a všetko, čo naň nadväzuje, okamžite a natrvalo
odstráni z živej databázy. **Pozor: tábory, ktoré vlastníš, sa zmažú spolu s tvojím účtom,
a to aj pre ostatných vedúcich, ktorí na nich spolupracujú.** Ak má tábor prežiť tvoj účet,
pred zmazaním ho preveď na niekoho iného alebo požiadaj iného člena, aby ho vytvoril nanovo.

## 9. Tvoje práva

Podľa GDPR máš právo:

- **na prístup** k svojim údajom (čl. 15) — Služba má export na jedno kliknutie:
  Nastavenia → Profil → _Stiahnuť moje údaje_, ktorý vytvorí JSON súbor s tvojím účtom a
  obsahom tvojich táborov
- **na opravu** nesprávnych údajov (čl. 16) — väčšina sa dá upraviť priamo v aplikácii
- **na vymazanie** údajov (čl. 17) — Nastavenia → Profil → _Zmazať účet_
- **na obmedzenie** spracúvania a **namietať** proti nemu (čl. 18 a 21)
- **na prenosnosť** údajov k inej službe (čl. 20) — JSON export vyššie je strojovo čitateľný
- **odvolať súhlas** kedykoľvek, bez vplyvu na už vykonané spracúvanie — pri AI funkcii
  odstránením Gemini kľúča

Ak chceš uplatniť právo, ktoré aplikácia sama nerieši, napíš na **michal@barnas.net**.
Odpoveď môžeš čakať do 30 dní. Keďže Službu prevádzkuje jeden človek vo voľnom čase, buď
prosím trpezlivý, a ak sa neozve, napíš znova.

Ak tvoje údaje zadal organizátor tábora a chceš ich odstrániť, obráť sa najprv na neho —
kontroluje ich on. Ak nereaguje, napíš na adresu vyššie a vyrieši sa to.

## 10. Bezpečnosť

- Celá komunikácia ide cez HTTPS.
- Heslá sa ukladajú ako bcrypt hashe; otvorený text sa nikdy nezapisuje.
- Gemini API kľúče a kľúče dvojfaktorového overenia sú šifrované aplikačným kľúčom.
- V Nastavenia → Zabezpečenie je k dispozícii voliteľné dvojfaktorové overenie a passkeys.
- Prístup k táborom sa kontroluje pri každej požiadavke voči tvojmu členstvu.
- Server sa udržiava aktualizovaný a databáza nie je dostupná z verejného internetu.

Žiadny systém nie je dokonale bezpečný. Ak nájdeš zraniteľnosť, nahlás ju prosím na
**michal@barnas.net** namiesto zverejnenia.

## 11. Údaje o deťoch

Služba je určená **dospelým, ktorí tábor organizujú** — vedúcim, animátorom a
koordinátorom. Účty sú pre nich.

Nie je navrhnutá na vedenie záznamov o **deťoch, ktoré na tábor chodia**, a nemal by si ju
tak používať. Konkrétne prosím nepíš mená detí, dátumy narodenia, zdravotné informácie,
kontaktné údaje ani fotografie do voľných textových polí, ako sú poznámky ku dňu,
narodeniny či názvy skupín.

Ak napriek tomu zadáš údaje o maloletom, **prevádzkovateľom týchto údajov si ty.** Ty
potrebuješ právny základ — v praxi súhlas rodiča alebo zákonného zástupcu — a ty nesieš
následky. Prevádzkovateľ Služby nemá s týmito deťmi žiadny vzťah a nijako sa nedozvie, že
sú tam ich údaje.

## 12. Automatizované rozhodovanie

Žiadne sa nevykonáva. Funkcia AI súhrnov vytvára text na čítanie pre ľudí; o nikom
nerozhoduje a nemá právne ani podobne významné účinky.

## 13. Zmeny týchto zásad

Tieto zásady sa môžu meniť spolu so Službou. Dátum hore vždy zodpovedá aktuálnej verzii.
O podstatných zmenách bude v aplikácii informácia.

## 14. Sťažnosti

Ak si myslíš, že sa s tvojimi údajmi zaobchádza nezákonne, môžeš podať sťažnosť dozornému
orgánu:

**Úrad na ochranu osobných údajov Slovenskej republiky**
Hraničná 12, 820 07 Bratislava 27, Slovenská republika
`dataprotection.gov.sk`

Sťažnosť môžeš podať aj orgánu v krajine EÚ, kde bývaš.
