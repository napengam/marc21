<h2>MARC 21</h2>

<pre>

<b style="font-size:1.5em">MA</b>chine-<b style="font-size:1.5em">R</b>eadable <b style="font-size:1.5em">C</b>ataloging record.

<b>marc21.php</b> a class to iterate over records of a file in marc 21 format

A description of the MARC 21 format can be found 
here <a href="//https://www.loc.gov/marc/umb/">https://www.loc.gov/marc/umb/</a>

Testdata located in directory <b>mrc</b> are derived 
from <a href="http://datendienst.d-nb.de/cgi-bin/mabit.pl?userID=testdat&pass=testdat&cmd=login">http://datendienst.d-nb.de/</a>

<b>NOTE:</b> The data files hold characters in utf 8 decomposed form. Because of this the string is normalized using 
a call to php function <i> normalizer_normalize </i> to bring it back into utf8 composed form, before it is stored. 
The reason for doing this is, comparing strings from the data file, against strings used by PHP.
In decomposed form, the encoding of special characters like umlauts &auml; &ouml; &uuml; etc uses more bytes, than 
in composed form that PHP is using. Next, some fonts might get in trouble to render decomposed utf 8 correctly.   



More on the subject of decomposed, composed utf 8 here: <a href="http://unicode.org/reports/tr15/">unicode.org</a>  
Here is more excellent information about this subject  <a href="http://kunststube.net/encoding/">http://kunststube.net/encoding/</a>
and even more interessting if you have to store utf-8 within a data base <a href="http://kunststube.net/encoding/">http://kunststube.net/frontback/</a>




Added code to  skip over NON-SORT BEGIN,NON-SORT END characters.
 

For usage of that reader look into index.php
A demo is here <a href="https://vz139.worldserver.net/marc21/">https://vz139.worldserver.net/marc21/</a>

<b>NOTE:</b> I do not check for any syntax errors within a file, I just <span style="color:red"><b>assume</b></span>,
that the file exists is readable and syntacticaly correct .

<b>NOTE: <span style="color:red">NEVER ASSUME</span> </b>


</pre>