<?php

$help_lng = $session->usr_i18n;
if(!in_array($session->usr_i18n,array('fr','en','us')))
	$help_lng = 'fr';
	
if($help_lng == 'fr')
{
?>
	<div class="client_help">
	<h5>La recherche s'effectue grâce à la boîte de dialogue qui se trouve en haut à gauche de l'écran. Sachez que vous pouvez utiliser les opérateurs ou caractères spéciaux suivants :</h5>
	<h5 style="border:#CCCCCC 2px solid">* , ? , ET , OU , SAUF , DANS , DERNIERS , TOUT&nbsp;&nbsp; &nbsp;(ou AND , OR , EXCEPT , LAST , ALL)</h5>
	
	<H5>Caractères de troncature</H5>
	
	<table>
		<tr>
			<td valign="top"><kbd class="ky">auto<b>*</b></kbd></td>
			<td valign="top"> : "automobile", "automate", "autoroute", ...</td>
		</tr>
		<tr>
			<td valign="top"><kbd class="ky">dé<b>?</b>it</kbd></td>
			<td valign="top"> : "délit", "débit", ...</td>
		</tr>
	</table>
	
	<H5>Visualiser toutes les photos / les dernières photos</H5>
	<table>
		<tr>
			<td valign="top"><kbd class="ky"><b>TOUT</b></kbd></td>
			<td valign="top"> : toutes les photos des collections sélectionnées</td>
		</tr>
		<tr>
			<td valign="top"><kbd class="ky"><b>LAST</b> 20</kbd></td>
			<td valign="top"> : les 20 dernières photos archivées dans la base</td>
		</tr>
	</table>
	
	<H5>Recherche multicritères</H5>
	Vous pouvez affiner votre recherche avec les opérateurs : ET, OU, SAUF ou DANS<br>
	<table>
		<tr>
			<td valign="top"><kbd class="ky">sport <b>ET</b> automobile</kbd></td>
			<td valign="top"> : photos comprenant les deux mots.</td>
		</tr>
		<tr>
			<td valign="top"><kbd class="ky">journal <b>OU</b> jt</kbd></td>
			<td valign="top"> : photos comprenant un mot ou l'autre (ou les deux).</td>
		</tr>
		<tr>
			<td valign="top"><kbd class="ky">cannes <b>SAUF</b> festival</kbd></td>
			<td valign="top"> : cannes, hors festival.</td>
		</tr>
		<tr>
			<td valign="top"><kbd class="ky">thalassa <b>DANS</b> titre</kbd></td>
			<td valign="top"> : photos où le terme est au moins présent dans le titre, en évitant par exemple celles où le terme est uniquement cité dans la légende.</td>
		</tr>
	</table>
	
	<center>
	<h3 style="background-color:#CCCCCC; color:#000000">Attention :</h3>
	<h4> pour chercher une phrase contenant un des mots-clé ci-dessus,<br/>utilisez les <i>&nbsp;guillemets&nbsp;</i> :</h4>
	<kbd class='tx'><i>"</i>C <b>dans</b> l'air<i>"</i></kbd>
	,  <kbd class='tx'><i>"</i><b>Et</b> Dieu créa la femme<i>"</i></kbd>
	, <kbd class='tx'><i>"</i>bijou en <b>or</b><i>"</i></kbd>
	, <kbd class='tx'><i>"</i><b>tout</b> le sport<i>"</i></kbd>
	, ...
	</center>
	</div>		
<?php
}
if($help_lng == 'en' || $help_lng == 'us')
{
?>
	<div class="client_help">
	<h5>The search can be made through a dialogue box which can be found up left of your screen. Please note that you can use the following specific characters or boolean operators :</h5>
	<h5 style="border:#CCCCCC 2px solid">* , ? ,  AND , OR , EXCEPT , IN, LAST , ALL</h5>
	
	<H5>Truncation characters :</H5>
	
	<table>
		<tr>
			<td valign="top"><kbd class="ky">autho<b>*</b></kbd></td>
			<td valign="top"> : "authority", "authorization", "autorized", ...</td>
		</tr>
		<tr>
			<td valign="top"><kbd class="ky">de<b>?</b>t</kbd></td>
			<td valign="top"> : "debt", "dent", ...</td>
		</tr>
	</table>
	
	<H5>View all pictures / last documents : </H5>
	<table>
		<tr>
			<td valign="top"><kbd class="ky"><b>ALL</b></kbd></td>
			<td valign="top"> : toutes les photos des collections sélectionnées</td>
		</tr>
		<tr>
			<td valign="top"><kbd class="ky"><b>LAST</b> 20</kbd></td>
			<td valign="top"> : The last 20 documents archived in database</td>
		</tr>
	</table>
	
	<H5>Multi criteria  search :</H5>
	You can refine your search with the following  operators : AND , OR , EXCEPT or IN<br>
	<table>
		<tr>
			<td valign="top"><kbd class="ky">sport <b>AND</b> cars</kbd></td>
			<td valign="top"> : documents including both words.</td>
		</tr>
		<tr>
			<td valign="top"><kbd class="ky">new <b>OR</b> recent</kbd></td>
			<td valign="top"> : documents including one word or the other (or both).</td>
		</tr>
		<tr>
			<td valign="top"><kbd class="ky">cannes <b>EXCEPT</b> festival</kbd></td>
			<td valign="top"> : documents including the word « Cannes », except the word « festival »..</td>
		</tr>
		<tr>
			<td valign="top"><kbd class="ky">thalassa <b>IN</b> title</kbd></td>
			<td valign="top"> : documents where the word is at least in the title, avoiding for instance the ones where the word is quoted in the caption only.</td>
		</tr>
	</table>
	
	<center>
	<h3 style="background-color:#CCCCCC; color:#000000">Be careful :</h3>
	<h4>To make a search in a sentence including one of the keywords above,<br/>please use the <i>quotation</i> marks :</h4>
	<kbd class='tx'><i>"</i>come <b>and</b> see<i>"</i></kbd>
	,  <kbd class='tx'><i>"</i><b>except</b> for<i>"</i></kbd>
	, <kbd class='tx'><i>"</i>at <b>last</b><i>"</i></kbd>
	, <kbd class='tx'><i>"</i><b>all</b> along<i>"</i></kbd>
	, ...
	</center>
	</div>		
<?php
}
