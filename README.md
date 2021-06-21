Videoconverter
==============

What keeps http://videoconverter.ukm.no running - most of the time.

Videoconverteren mottar filer fra UKM.no, konverterer disse, sender de til [lagring](https://github.com/UKMNorge/videostorage), og varsler UKM.no om at filmen nå er klar til avspilling. Så enkelt. Ish.

## Opplasting og registrering
Filen lastes opp i chunks via [last_opp.php](last_opp.php) som lagrer filen i `temp_storage/inbox`. 
Når filen er ferdig opplastet returneres filnavnet som json-data.

Opplasteren gjør så et nytt kall til [registrer.php](registrer.php) hvor den sender med påkrevd data (bl.a. filnavn i inbox fra [last_opp.php](last_opp.php), blog_id, beskrivelser [osv.](registrer.php#L35)), og får `cron_id` i retur.

Filmen er nå en [Jobb](class/Jobb.php) som skal konverteres.

### Cron ID? 🤔
CronID henger igjen fra den første versjonen av converteren, da converteren ble sett ned på som en enkel cron-jobb. Siden den tid har den fungert som en unik ID for alle konverteringsjobber. I dag er CronID det du får tilbake når du kjører [Jobb->getID()](class/Jobb.php), og følger filmen også på UKM.no

## Konvertering
Alle jobber (filmer) konverteres i tre runder: [ConvertFirst](cron/convert_first.cron.php), [ConvertSecond](cron/convert_second.php) og [ConvertArchive](cron/convert_archive.cron.php). Det er [cron-jobbene](cron/) som sikrer at dette skjer i riktig rekkefølge.

Cron-jobbene benytter hver sin [Convert-klasse](/class/Convert/), som definerer arbeidet som skal gjøres for hver runde. 

F.eks. vil du se at konstanten `PRESET` er definert på de ulike [Convert-klassene](class/Convert): denne angir hvilken hastighet vi ønsker at de ulike versjonene skal encodes i.

### Prioritering
Vi ønsker alltid å tilgjengeliggjøre filmer for publikum fortest mulig, og det er derfor vi har [ConvertFirst](cron/convert_first.cron.php)- og [ConvertSecond](cron/convert_second.php)-konverteringene. Disse produserer filmer i ca lik kvalitet, men førstegangskonverteringen gjør det fort og gæli, og gir en relativt stor videofil. Andregangskonverteringen gjør dette på nytt, med tregere preset, og mindre fil (og reduserer derfor båndbreddekravet for UKM-TV).

Så lenge det finnes en førstegangskonvertering som ikke er kjørt, vil vi ikke starte en andregangs eller arkiv-konvertering.

### Versjoner

#### UKM-TV
I UKM-TV ønsker vi tre filer: [Desktop-versjon](class/Versjon/HD.php), [Mobil-versjon](class/Versjon/Mobil.php) og et [bilde](class/Versjon/Bilde.php). [ConvertFirst](cron/convert_first.cron.php)- og [ConvertSecond](cron/convert_second.php)-konverteringene lager alle disse tre utgavene.

#### Arkiv
I arkivet ønsker vi en [høyoppløselig versjon](class/Versjon/Arkiv.php), en [metadatafil](class/Versjon/Metadata.php) og et [bilde](class/Versjon/Bilde.php). 

For å få god kvalitet på minst mulig plass, bruker [ConvertArchive](Convert/Archive.php) en treg preset. 

#### Preset VS Versjon 😵‍💫
Merk at det er [Convert-klassene](/class/Convert/) som definerer hvilke preset (hastigheten konverteringen kan utføres med) som skal benyttes, mens [Versjonene](/class/Versjon/) definerer hvilken bitrate, oppløsning osv (kvaliteten) som skal oppnås.

## Store / Archive
- [Store](cron/store.cron.php)-cron plukker opp alle konverterte filer som skal flyttes til lagringsserveren, og sender de dit via FileCurl.
- [Archive](cron/archive.cron.php)-cron må ikke forveksles med [ConvertArchive](Convert/Archive.php)-cron, som gjør selve konverteringen. [Archive](cron/archive.cron.php)-cron plukker opp alle filer som er ferdig konvertert i arkiverings-versjonene, og flytter de til dig.ark-serveren (som er tilgjengelig via et NFS-share).


## Status-prosessen

- Lastet opp fil får `status:registrert`
- [ConvertFirst](cron/convert_first.cron.php)-konvertering
    - Startet: `status:convert`, `first_convert:converting`
    - Fullført: `status:store`, `first_convert:done`
- [Store](cron/store.cron.php)
    - Startet: `status:transferring`
    - Fullført: `status:convert`
- [ConvertSecond](cron/convert_second.cron.php)-konvertering
    - Startet: `status:convert`, `final_convert:converting`
    - Fullført: `status:store`, `final_convert:done`
- [Store](cron/store.cron.php)
    - Startet: `status:transferring`
    - Fullført: `status:convert`
- [ConvertArchive](cron/convert_archive.cron.php)-konvertering
    - Startet: `status:convert`, `archive_convert:converting`
    - Fullført: `status:archive`, `archive_convert:done`
- [Archive](cron/archive.cron.php)
    - Startet: `status:archiving`
    - Fullført: `status:done`
- Ved fullført konvertering og akivering har vi: `status:done`, `first_convert:done`, `final_convert:done`, `archive_convert:done`

Går noe galt, vil jobben merkes med `status:crashed`.

Hvis jobben blir slettet, merkes den med `status:does_not_exist`