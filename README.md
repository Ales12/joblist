# Jobliste
Hier können User neue Arbeitstätte anlegen und dann einen Job eintragen, sowie Nebenjobs. Auch kann hinterlegt werden, wann Öffnungszeiten sind oder ob noch jemand gesucht wird. Arbeitsplätze können sowohl im ACP als auch im Forum neu angelegt werden. **ACHTUNG!!** Gästen muss die Erlaubnis, Berufe anzulegen, erst über die Gruppeneinstellungen entzogen werden!!! Standardmäßig ist es abgehakt.

## Pfad
misc.php?action=joblist

## Datenbank
joblist
<br /> <br />
in usertabelle
- jtitle = Wird der Hauptberuf eingetragen
- jid = ID zur Arbeitsstelle
- sjtitle = Wird der Nebenberuf eingetragen
- sjid = ID von Nebenjob

## variabeln
**header**
`` {$joblist_global} ``
**modcp_nav_users**
``{$nav_joblist}``
**member_profile**
``{$memprofile['job']}``

## templates 
- joblist 	
- joblist_add_job 	
- joblist_bit 	
- joblist_global 	
- joblist_join_job 	
- joblist_modcp 	
- joblist_modcp_bit 	
- joblist_modcp_nav 	
- joblist_nav 	
- joblist_options 	
- joblist_otherinfos 	
- joblist_staff 	
- joblist_tabcontent

## CSS 
**joblist.css**
```.joblist{
	display: flex;
}

/* Style the tab */
.tab {
  overflow: hidden;
	display: flex;
	 flex-flow: column wrap;
}

/* Style the buttons inside the tab */
.tab button {
  background-color: inherit;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 14px 16px;
  transition: 0.3s;
  font-size: 17px;
}

/* Change background color of buttons on hover */
.tab button:hover {
  background-color: #ddd;
}

/* Create an active/current tablink class */
.tab button.active {
  background-color: #ccc;
}

/* Style the tab content */
.tabcontent {
  display: none;
  padding: 6px 12px;
	box-sizing: border-box;
	width: 100%;
  animation: fadeEffect 1s; /* Fading effect takes 1 second */
}

/* Go from zero to full opacity */
@keyframes fadeEffect {
  from {opacity: 0;}
  to {opacity: 1;}
}

.joblist_infotext{
	margin: 20px;
	text-align: justify;
	font-size: 13px;
}



.joblist_job_flex{
	display: flex;
	flex-flow: row wrap;
}

.joblist_job_flex > div{
	margin: 5px;	
}

.joblist_job{
	width: 33%;
}

.joblist_job_top{
	background: #efefef;
  color: #333;
  border-top: 1px solid #fff;
  border-bottom: 1px solid #ccc;
  padding: 5px;
	box-sizing: border-box;
	text-align: center;
}

.joblist_job_desc{
	height: 100px;
	overflow: auto;
	padding: 2px 5px;
	box-sizing: border-box;
	text-align: justify;
}

.joblist_job_staff_top{
	text-align: center;	
}

.joblist_job_staff{
	padding: 10px;
	box-sizing: border-box;
	max-height: 100px;
	overflow: auto;
}

	.joblist_staff{
	padding: 2px 5px;		
}

.joblist_staff::before{
		content: "» ";
	padding-right: 2px;
}

.joblist_otherinfos{
	padding: 10px 20px;	
}```
