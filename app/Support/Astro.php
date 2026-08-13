<?php
namespace App\Support;


class Astro {

    private static function CalculateMoonPhases( $Y ) {
        //Converted from Basic by Roger W. Sinnot, Sky & Telescope, March 1985.
        //Converted from javascript by Are Pedersen 2002
        //Javascript found at http://www.stellafane.com/moon_phase/moon_phase.htm

        $R1 = 3.14159265 / 180;
        $U = false;
        $s = ""; // Formatted Output String
        $K0 = intval(($Y-1900)*12.3685);
        $T = ($Y-1899.5) / 100;
        $T2 = $T*$T;
        $T3 = $T*$T*$T;
        $J0 = 2415020 + 29*$K0;
        $F0 = 0.0001178*$T2 - 0.000000155*$T3;
        $F0 += (0.75933 + 0.53058868*$K0);
        $F0 -= (0.000837*$T + 0.000335*$T2);
        //X In the Line Below, F is not yet initialized, and J is not used before it set in the FOR loop.
        //X J += intval(F); F -= INT(F);
        //X Ken Slater, 2002-Feb-19 on advice of Pete Moore of Houston, TX
        $M0 = $K0*0.08084821133;
        $M0 = 360*($M0 - intval($M0)) + 359.2242;
        $M0 -= 0.0000333*$T2;
        $M0 -= 0.00000347*$T3;
        $M1 = $K0*0.07171366128;
        $M1 = 360*($M1 - intval($M1)) + 306.0253;
        $M1 += 0.0107306*$T2;
        $M1 += 0.00001236*$T3;
        $B1 = $K0*0.08519585128;
        $B1 = 360*($B1 - intval($B1)) + 21.2964;
        $B1 -= 0.0016528*$T2;
        $B1 -= 0.00000239*$T3;
        for ( $K9=0; $K9 <= 28; $K9=$K9+0.5 ) {
            $J = $J0 + 14*$K9; $F = $F0 + 0.765294*$K9;
            $K = $K9/2;
            $M5 = ($M0 + $K*29.10535608)*$R1;
            $M6 = ($M1 + $K*385.81691806)*$R1;
            $B6 = ($B1 + $K*390.67050646)*$R1;
            $F -= 0.4068*sin($M6);
            $F += (0.1734 - 0.000393*$T)*sin($M5);
            $F += 0.0161*sin(2*$M6);
            $F += 0.0104*sin(2*$B6);
            $F -= 0.0074*sin($M5 - $M6);
            $F -= 0.0051*sin($M5 + $M6);
            $F += 0.0021*sin(2*$M5);
            $F += 0.0010*sin(2*$B6-$M6);
            $F += 0.5 / 1440; //Adds 1/2 minute for proper rounding to minutes per Sky & Tel article
            $J += intval($F); $F -= intval($F);
            //Convert from JD to Calendar Date
            $julian=$J+round($F);
            $s = jdtogregorian ($julian);

            //half K
            if (($K9-floor($K9))>0){
                if (!$U){
                    //New half
                    $phases[$s]="ny2";
                }else{
                    //Full half
                    $phases[$s]="ne2";
                }

            }else{
                //full K
                if ( !$U ){
                    $phases[$s]="ny";
                }else{
                    $phases[$s]="ne";
                }
                $U = !$U;
            }
        } // Next
        return $phases;
    }

    private static function GetMoonPhase($timestamp) {
        return self::CalculateMoonPhases(date("Y",$timestamp));
    }

    public static function getData($szerokoscGeo, $dlugoscGeo) {
        $licznik=0;
        $czas=time();
        $czast=$czas;
        $moonik = self::GetMoonPhase($czas);
        $day=date("n/j/Y",$czas);
        while (true) {
            if (array_key_exists($day, $moonik)) {
                if (($moonik[$day] == "ne") || ($moonik[$day] == "ny")) { break; }
            }
            $czas-=86400;
            $day=date("n/j/Y",$czas);
        }
        if ($moonik[$day]=="ne") $tmp="ny";
        if ($moonik[$day]=="ny") $tmp="ne";
        $czasp=$czas;
        $czas=$czast;

        $day=date("n/j/Y",$czas);
        while (true) {
            if (array_key_exists($day, $moonik)) {
                if ($moonik[$day] == $tmp) { break; }
            }
            $czas+=86400;
            $day=date("n/j/Y",$czas);
        }
        $czasd=$czas;
        //ne pelnia
        $roznica=($czasd-$czasp)/86400;

        $zn=false;
        $day=date("n/j/Y",$czast);
        if (array_key_exists($day, $moonik)) {
            if ($moonik[$day] == "ne") {
                $dp=0;
                $dn=$roznica+1;
                $zn=true;
            }
            if ($moonik[$day] == "ny") {
                $dp=$roznica+1;
                $dn=0;
                $zn=true;
            }
        }
        if ($zn==false)
        {
            $np=true;
            if ($tmp=="ne") $np=false;
            $od=($czast-$czasp)/86400;
            $do=($czasd-$czast)/86400;
            $odp=($od/($roznica+1))*10;
            $dop=($do/($roznica+1))*10;
        }

        $info="";
        $jestk=false;
        if ($zn==true)
        {
            if ($dp==0)
            {
                $ksDni = $dn;
                $ksInfo = "pełnia; do nowiu";
                if ($dn==1) $dn.=" dzień";
                if ($dn<>1) $dn.=" dni";
                $info="pełnia; do nowiu $dn";
                $info1="<b>pełnia</b>";
                $info2="<b>$dn</b> do nowiu";
                $obr="k11";
                $jestk=true;
            };
            if ($dn==0)
            {
                $ksDni = $dp;
                $ksInfo = "nów; do pełni";
                if ($dp==1) $dp.=" dzień";
                if ($dp<>1) $dp.=" dni";
                $info="nów; do pełni $dp";
                $info1="<b>nów>";
                $info2="<b>$dp</b> do pełni";
                $obr="k1";
                $jestk=true;
            };
        };

        if ($zn==false)
        {
            $tmpod=$od;
            $ksDni = $od." / ".$do;
            if ($od==1) $od.=" dzień";
            if ($od<>1) $od.=" dni";
            if ($do==1) $do.=" dzień";
            if ($do<>1) $do.=" dni";
            if ($np==true)
            {
                $ksInfo = "po pełni / do nowiu";
                $info="$od po pełni; do nowiu $do";
                $info1="<b>$od</b> po pełni";
                $info2="<b>$do</b> do nowiu";
                $tnij=round($dop);$tnij=$tnij+1;
                $obr="ks".$tnij;
            };
            if ($np==false)
            {
                $ksInfo = "po nowiu / do pełni";
                $info="$od po nowiu; do pełni $do";
                $info1="<b>$od</b> po nowiu";
                $info2="<b>$do</b> do pełni";
                $tnij=round($dop);$tnij=11-$tnij;
                $obr="k".$tnij;
            };
        };

        //data
        $tabmie=array(1=>"stycznia","lutego","marca","kwietnia","maja","czerwca","lipca","sierpnia","września","października","listopada","grudnia");
        $tabdni=array(0=>"Niedziela","Poniedziałek","Wtorek","Środa","Czwartek","Piątek","Sobota");
        $mie=date("n",time());
        $miesiac=$tabmie[$mie];
        $dt=getdate(time());
        $dzien=$tabdni[$dt["wday"]];
        $dz=date("j",time());
        $rok=date("Y",time());
        //koniec data

        //długość dnia
        $sun_info = date_sun_info(time(), $szerokoscGeo, $dlugoscGeo);
        $wsch = date("H:i", $sun_info['sunrise']);
        $zach = date("H:i", $sun_info['sunset']);
        $wschod = $sun_info['sunrise'];
        $zachod = $sun_info['sunset'];
        $dlugosc_dzisiaj = $sun_info['sunset'] - $sun_info['sunrise'];
        $dlugoscDnia = gmdate("H", $dlugosc_dzisiaj)." h ".date("i'", $dlugosc_dzisiaj);

        //który dzień roku
        $odn = date("z", time())+1;
        //ile dni do końca roku
        $donr = 365;
        if (date("L", time()) == 1) $donr += 1;
        $donr -= $odn;

        //Dłuższy od najkrótszego o:
        $najkrotszyDzien = 1000000000000000000;
        for($i = 1; $i <= 31; $i++)
        {
            $sun_info = date_sun_info(mktime(1,1,0,12,$i,date("Y",time())), $szerokoscGeo, $dlugoscGeo);
            $najkrotszy_dzien = $sun_info['sunset'] - $sun_info['sunrise'];
            if ($najkrotszy_dzien < $najkrotszyDzien) $najkrotszyDzien = $najkrotszy_dzien;
        }
        $najkrotszy_dzien = abs($dlugosc_dzisiaj - $najkrotszyDzien);
        $don = gmdate("H", $najkrotszy_dzien)." h ".gmdate("i'", $najkrotszy_dzien);

        //Krótszy od najdłuższego o:
        $najdluzszyDzien = 0;
        for($i = 1; $i <= 31; $i++)
        {
            $sun_info = date_sun_info(mktime(1,1,0,6,$i,date("Y",time())), $szerokoscGeo, $dlugoscGeo);
            $najdluzszy_dzien = $sun_info['sunset'] - $sun_info['sunrise'];
            if ($najdluzszy_dzien > $najdluzszyDzien) $najdluzszyDzien = $najdluzszy_dzien;
        }
        $najdluzszy_dzien = abs($najdluzszyDzien - $dlugosc_dzisiaj);
        $kod = gmdate("H", $najdluzszy_dzien)." h ".gmdate("i'", $najdluzszy_dzien);


        //astro2
        $odn1 = $odn;
        $donr1 = $donr;
        $dlugoscDnia1 = $dlugoscDnia;
        $don1 = $don;
        $kod1 = $kod;
        $wsch1 = $wsch;
        $zach1 = $zach;
        $obr1 = $obr;
        $ksDni1 = $ksDni;
        $ksInfo1 = $ksInfo;
        $dz1 = $dz;
        $miesiac1 = $miesiac;

        $czas2 = time() + 86400;

        $licznik=0;
        $czas=$czas2;
        $czast=$czas;
        $moonik = self::GetMoonPhase($czas);

        $day=date("n/j/Y",$czas);
        while (true) {
            if (array_key_exists($day, $moonik)) {
                if (($moonik[$day] == "ne") || ($moonik[$day] == "ny")) { break; }
            }
            $czas-=86400;
            $day=date("n/j/Y",$czas);
        }

        if ($moonik[$day]=="ne") $tmp="ny";
        if ($moonik[$day]=="ny") $tmp="ne";
        $czasp=$czas;
        $czas=$czast;

        $day=date("n/j/Y",$czas);
        while (true) {
            if (array_key_exists($day, $moonik)) {
                if ($moonik[$day] == $tmp) { break; }
            }
            $czas+=86400;
            $day=date("n/j/Y",$czas);
        }
        $czasd=$czas;
        //ne pelnia
        $roznica=($czasd-$czasp)/86400;

        $zn=false;
        $day=date("n/j/Y",$czast);
        if (array_key_exists($day, $moonik)) {
            if ($moonik[$day] == "ne")
            {
                $dp=0;
                $dn=$roznica+1;
                $zn=true;
            }
            if ($moonik[$day] == "ny")
            {
                $dp=$roznica+1;
                $dn=0;
                $zn=true;
            }
        }
        if ($zn==false)
        {
            $np=true;
            if ($tmp=="ne") $np=false;
            $od=($czast-$czasp)/86400;
            $do=($czasd-$czast)/86400;
            $odp=($od/($roznica+1))*10;
            $dop=($do/($roznica+1))*10;
        }

        $info="";
        $jestk=false;
        if ($zn==true)
        {
            if ($dp==0)
            {
                $ksDni = $dn;
                $ksInfo = "pełnia; do nowiu";
                if ($dn==1) $dn.=" dzień";
                if ($dn<>1) $dn.=" dni";
                $info="pełnia; do nowiu $dn";
                $info1="<b>pełnia</b>";
                $info2="<b>$dn</b> do nowiu";
                $obr="k11";
                $jestk=true;
            };
            if ($dn==0)
            {
                $ksDni = $dp;
                $ksInfo = "nów; do pełni";
                if ($dp==1) $dp.=" dzień";
                if ($dp<>1) $dp.=" dni";
                $info="nów; do pełni $dp";
                $info1="<b>nów>";
                $info2="<b>$dp</b> do pełni";
                $obr="k1";
                $jestk=true;
            };
        }
        else
        {
            $tmpod=$od;
            $ksDni = $od." / ".$do;
            if ($od==1) $od.=" dzień";
            if ($od<>1) $od.=" dni";
            if ($do==1) $do.=" dzień";
            if ($do<>1) $do.=" dni";
            if ($np==true)
            {
                $ksInfo = "po pełni / do nowiu";
                $info="$od po pełni; do nowiu $do";
                $info1="<b>$od</b> po pełni";
                $info2="<b>$do</b> do nowiu";
                $tnij=round($dop);$tnij=$tnij+1;
                $obr="ks".$tnij;
            };
            if ($np==false)
            {
                $ksInfo = "po nowiu / do pełni";
                $info="$od po nowiu; do pełni $do";
                $info1="<b>$od</b> po nowiu";
                $info2="<b>$do</b> do pełni";
                $tnij=round($dop);$tnij=11-$tnij;
                $obr="k".$tnij;
            };
        };

        //data

        $tabmie=array(1=>"stycznia","lutego","marca","kwietnia","maja","czerwca","lipca","sierpnia","września","października","listopada","grudnia");
        $tabdni=array(0=>"Niedziela","Poniedziałek","Wtorek","Środa","Czwartek","Piątek","Sobota");
        $mie=date("n",$czas2);
        $miesiac=$tabmie[$mie];
        $dt=getdate($czas2);
        $dzien=$tabdni[$dt["wday"]];
        $dz=date("j",$czas2);
        $rok=date("Y",$czas2);
        //koniec data

        //długość dnia
        $sun_info = date_sun_info($czas2, $szerokoscGeo, $dlugoscGeo);
        $wsch = date("H:i", $sun_info['sunrise']);
        $zach = date("H:i", $sun_info['sunset']);
        $wschod = $sun_info['sunrise'];
        $zachod = $sun_info['sunset'];
        $dlugosc_dzisiaj = $sun_info['sunset'] - $sun_info['sunrise'];
        $dlugoscDnia = gmdate("H", $dlugosc_dzisiaj)." h ".date("i'", $dlugosc_dzisiaj);

        //który dzień roku
        $odn = date("z", $czas2)+1;
        //ile dni do końca roku
        $donr = 365;
        if (date("L", $czas2) == 1) $donr += 1;
        $donr -= $odn;

        //Dłuższy od najkrótszego o:
        $najkrotszyDzien = 1000000000000000000;
        for($i = 1; $i <= 31; $i++)
        {
            $sun_info = date_sun_info(mktime(1,1,0,12,$i,date("Y",$czas2)), $szerokoscGeo, $dlugoscGeo);
            $najkrotszy_dzien = $sun_info['sunset'] - $sun_info['sunrise'];
            if ($najkrotszy_dzien < $najkrotszyDzien) $najkrotszyDzien = $najkrotszy_dzien;
        }
        $najkrotszy_dzien = abs($dlugosc_dzisiaj - $najkrotszyDzien);
        $don = gmdate("H", $najkrotszy_dzien)." h ".gmdate("i'", $najkrotszy_dzien);

        //Krótszy od najdłuższego o:
        $najdluzszyDzien = 0;
        for($i = 1; $i <= 31; $i++)
        {
            $sun_info = date_sun_info(mktime(1,1,0,6,$i,date("Y",$czas2)), $szerokoscGeo, $dlugoscGeo);
            $najdluzszy_dzien = $sun_info['sunset'] - $sun_info['sunrise'];
            if ($najdluzszy_dzien > $najdluzszyDzien) $najdluzszyDzien = $najdluzszy_dzien;
        }
        $najdluzszy_dzien = abs($najdluzszyDzien - $dlugosc_dzisiaj);
        $kod = gmdate("H", $najdluzszy_dzien)." h ".gmdate("i'", $najdluzszy_dzien);

        $odn2 = $odn;
        $donr2 = $donr;
        $dlugoscDnia2 = $dlugoscDnia;
        $don2 = $don;
        $kod2 = $kod;
        $wsch2 = $wsch;
        $zach2 = $zach;
        $obr2 = $obr;
        $ksDni2 = $ksDni;
        $ksInfo2 = $ksInfo;
        $dz2 = $dz;
        $miesiac2 = $miesiac;

        $result['today']['day'] = $dz1;
        $result['today']['month'] = $miesiac1;
        $result = [
            'today' => [
                'day' => $dz1,
                'month' => $miesiac1,
                'year' => date("Y", time()),
                'dayOfYear' => $odn1,
                'toNewYear' => $donr1,
                'dayLength' => $dlugoscDnia1,
                'dayLonger' => $don1,
                'dayShorter' => $kod1,
                'sunSet' => $wsch1,
                'sunRise' => $zach1,
                'moonInfo' => $ksInfo1,
                'moonIcon' => $obr1,
                'moonDays' => $ksDni1,
            ],
            'yesterday' => [
                'day' => $dz2,
                'month' => $miesiac2,
                'dayOfYear' => $odn2,
                'toNewYear' => $donr2,
                'dayLength' => $dlugoscDnia2,
                'dayLonger' => $don2,
                'dayShorter' => $kod2,
                'sunSet' => $wsch2,
                'sunRise' => $zach2,
                'moonIcon' => $obr2,
                'moonDays' => $ksDni2,
                ],

        ];

        return $result;
    }
}