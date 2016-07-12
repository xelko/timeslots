# TIMESLOTS

Génération de tranches horaires à partir de règles d'ouverture et de fermeture

## Formats des règles
```
  <type de règles>: [<valeur unitaire>] ou [<valeur de début incluse>-<valeur de fin exclue>] [, ...];
```

## Types de règle

### définition des périodes

#### periods: ou p: (Définition des périodes en minutes depuis minuit [0..1440])
##### exemple
```
  periods:480-720,730-910;
```  

### filtres

#### days: ou d: (Définition des jours au format YYYYMMDD)
##### exemple
```
  days:20160317,20151224,20140125-20140129;
```
  
#### weekdays: ou wd: (Définition des jours de la semaine au format [0..6] dimanche au samedi) (utilise le modulo 7)
##### exemple
```  
  weekdays:0,2,4-6;
```
  
#### birddays: ou bd: (Définition des jours anniversaires au format MMDD)
##### exemple
```
  birddays:0130,0327,0401-0415;
```
  
#### yeardays: ou yd: (Définition des jours de l'année au format [0..366])
##### exemple
```
  yeardays:27,42,128-217;
```
  
#### specialdays: ou sd: (Définition des jours spéciaux) (easter, easterMonday, ascension, pentecost) ou (pâques, lundiPâques, pentecôte)
##### exemple
```
  specialdays:easter, easterMonday, ascension, pentecost;
```
  
## Exemple générale

```
$calendar = new \Xelko\TimeSlots\Calendar();  
  
// aligne sur minuit  
$calendar->setMidnightAlignment(true);  

// plage de 30 minutes  
$calendar->setGranularity(30);  

// memorisation des 10 derniers calculs (pour accélération)  
$calendar->setCacheSize(10);  

// tout les jours de 510 minutes à 600 minutes (de 8h30 à 10h00)  
$calendar->addOpenRules(["periods:510-600"]);  

// chaque lundi au vendredi du mois de mars de 480 minutes à 720 minutes et 780 minutes à 1000 minutes   
// ainsi que chaque 15 mai et 17 juin de 480 minutes à 1000 minutes   
$calendar->addOpenRules([
    "wd:1-5;bd:0301-0331;periods:480-720,780-1000",
    "bd:0515,0617;periods:480-1000"
]);   

// fermeture de 0 à 1440 (toute la journée) (prioritaire sur ouverture) chaque jour de pâques et lundi de Pâques et jour de pentecôte  
// ainsi que chaque 1 janvier, 1 mai, 8 mai, 14 juillet fermé le matin  
$calendar->addCloseRules(["sd:easter,easterMonday,ascension;periods:0-1440","bd:0101,0501,0508,0714;periods:0-720"]);  
    
// récupère les périodes d'ouverture du 01 janvier 2014 12:20:00 au 31 décembre 2016 12:20:00  
$aixiaperiods = $calendar->getPeriodsOfDays(new \DateTime("2014-01-01 12:20:00"),new \DateTime("2016-12-31  12:20:00"));    
    
// récupère les tranches d'horaires d'ouverture valides du 01 janvier 2014  12:20:00 au 31 décembre 2016  12:20:00     
$aixiaperiods = $calendar->getTimeSlotsOfDays(new \DateTime("2014-01-01  12:20:00"),new \DateTime("2016-12-31 12:20:00"));      
```