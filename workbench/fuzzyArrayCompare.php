<?php
require 'Hubbub/Utility.php';
use Hubbub\Utility;

$dataSets = [];
$needleSet = [];

$dataSets['ircNetworks'] = [
    '1andallIRC.net', '1chan', '247fixes', '2600net', '2ch', '36hz', '420-Hightimes', '42IRC', '4funbabbelchat.nl',
    '6IRCNet', '7ter-himmel-irc.net', '7thRound', 'A1-Chat', 'Abandoned-IRC', 'Abjects', 'AbleNET', 'absoluty-irc.org',
    'AccessIRC', 'AceIRC', 'AFNet', 'AfraidIRC', 'After-All', 'AfterNET', 'AfterX', 'Aitvaras', 'Albasoul.Com',
    'Albnetwork.net', 'AllNetwork', 'AllredNC-IRC', 'AlphaIRC', 'Andromeda.Fr', 'Angel-IRC', 'angkorchat', 'Aniterasu',
    'Aniverse', 'AnonNet', 'AnonOps', 'AnthroChat', 'Anynet', 'AONL', 'apagnuNet', 'AppliedIRC', 'ARCNet', 'ArloriaNET',
    'ASoTnet', 'Asylo', 'AsylumNet', 'AthemeNet', 'atlantis-irc', 'Atrum', 'AureAWorld', 'AustNet', 'Autistici.org',
    'AUXnet', 'Awfulnet', 'Axenet', 'AyoChat', 'Azzurra', 'B2IRC', 'BadnikNET', 'BakaShiMoe', 'BamzeNET', 'BanglaCafe',
    'Bastard-IRC', 'BDSM-Net.com', 'BestBG', 'BetaChat', 'BgIRC', 'Bikers-IRC', 'BinaryIRC', 'BinHex', 'BIRTIJA',
    'BitsJoint-IRC', 'Black-Cell.net', 'Blafasel', 'Blitzed', 'Bluechat', 'BlueFantasy', 'bluefantasychat', 'Boesechat',
    'BOLChat', 'Bondage.International', 'BoomIRC', 'BoredIRC', 'Born2Chat', 'BR-online', 'BrasChat', 'BrIRC', 'btrIRC',
    'Bullet-IRC', 'ByNets', 'byxnet', 'Caelestia', 'CaffieNET', 'CakeForceUK', 'Canternet', 'ChaosIRC',
    'ChaoticNetworks', 'Chaoz-IRC', 'Chat-Hispania', 'Chat-Solutions', 'Chat.be', 'Chat4All.org', 'Chat95', 'ChatAbout',
    'ChatCafe', 'ChatCore', 'Chaterz.nl', 'chatfreaks', 'ChatIRC', 'ChatJunkies', 'ChatLounge', 'ChatMusic', 'ChatNet',
    'chatnets', 'Chatopia', 'Chatpat', 'ChatPoint', 'ChatSansar', 'ChatSpike', 'Chatters', 'Chattersweb', 'ChatZona',
    'ChillNet', 'ChLame', 'ChristianChat', 'Cinos', 'CityTchat', 'CnCIRC', 'CodeAnime', 'Codeo', 'Cognet', 'Coldfront',
    'CollectiveIRC', 'ConflictIRC', 'Coolsmile.net', 'Cornerstone', 'cqchat.co.in', 'Criten', 'CrystalIRC', 'Cryto',
    'Cuckoo', 'Cyanide-x', 'CyberArmy', 'Cyberax', 'CyberFM', 'D4RCnet', 'DALnet', 'DALNet.RU', 'DareNET',
    'Dark-Tou-Net', 'DarkerNet', 'DarkMyst', 'darkscience', 'DarkSpirit', 'DatNode', 'De.Co.I', 'DeepIRC',
    'Deepspace.org', 'degunino.net', 'DejaToons', 'delfi.ee', 'delfi.lt', 'deltaanime.net', 'DeluXe-IRC', 'DesireNet',
    'DigitalIRC', 'DivineIRC', 'Dogm-Network', 'DorksNet', 'Dostitime', 'DragonIRC', 'Dream-IRC', 'dreams-fr',
    'dreamterra.ru', 'DrLnet', 'DynastyNet', 'Eagles-myIRC', 'Echo34', 'ECNet', 'EdgeOfPerspective', 'EFnet',
    'ElectroCode', 'EnterTheGame', 'EntropyNet', 'EpiKnet', 'EQEmu', 'EsperNet', 'EstIRC', 'etree', 'euIRC',
    'EuropeIRC', 'EuropNet', 'Evolu.NET', 'EvoSurge', 'Ewnix', 'ExChat', 'ExChat.net', 'ExodusIRC', 'eXolia',
    'ExplosionIRC', 'ext3', 'Falai', 'Fansub-IRC', 'FDFnet', 'FEFNet', 'Fewona', 'FireIRC', 'FlagRun', 'FluxNet',
    'Foonetic', 'ForestNet', 'ForeverChat', 'Freed0m4All', 'Freedom2IRC', 'freeirc.de', 'freenode', 'FreeQuest',
    'freestaar-place', 'Fresh', 'FreshChat', 'FriendsIRC', 'Frozen-IRC', 'FunIRC', 'FunNet', 'FurNet', 'Futuragora',
    'FyreChat', 'GalaxyEvolutions', 'GalaxyNet', 'GameSurge', 'GeekNode', 'GeeksAndGamers', 'GeekShed', 'Genscripts',
    'GenteChats', 'German-Elite', 'german-freakz.net', 'German-IRC', 'GG.ST', 'GhostsIRC', 'GigaCrew', 'GigaIRC',
    'GigIRC', 'GIMPnet', 'GlobalChat.nl', 'GlobalGamers', 'GlobalIRC', 'GlobalIRC.it', 'GNETT.org', 'GoreanPathWays',
    'gorfnets', 'GreatShark', 'GreekIRC', 'Griefplay', 'GRNet', 'GTANet', 'GTAXLnet', 'hackint', 'hashmark.net',
    'Hellcat', 'Hellenic', 'hemligt.net', 'HiddenIRC', 'Hira', 'HoaxNET', 'Homies4life', 'homo.net', 'honotua',
    'Hub4Ever.Org', 'Huskynet.info', 'i.r.cx', 'I6.IO', 'IceDawn', 'IceStarIRC', 'idlemonkeys.net', 'ImaginaryNET',
    'Immortal-Anime', 'India Chat', 'IndirectIRC', 'IndoIRC', 'IndoLynx', 'Inframonde', 'Inside-IRC', 'Inside3D',
    'Interlinked', 'Interlinked.me', 'internetz.me', 'irc-attitude', 'IRC-GLOBAL', 'IRC-Hispano', 'IRC-IRC.de',
    'IRC-Mania', 'IRC.BY', 'IRC.tl', 'IRC2', 'IRC4Fun', 'IRC6.LV', 'IrcCity', 'IRCd-Net', 'IRCGate.it', 'IRCHaven',
    'IRCHighWay', 'IRCItaly', 'IRCity', 'iRCLiNE', 'IRCLink', 'IRCLuxe', 'IRCLuxe.RU', 'IRCMojo', 'IRCnet', 'IRCnet.gr',
    'IrcNet.ru', 'IRCPlanet', 'IrCQ-Net', 'IRCs.gr', 'IRCsource', 'IRCstorm', 'IRCtoo', 'IRCube', 'IrcWorld',
    'ircx.net.pl', 'irc|forex', 'IRDSI', 'Italian-Amici', 'iTNA', 'Ivrit', 'IWWFNet', 'iZ-smart.net', 'jaundies',
    'Jeuxvideo', 'Join2Chat', 'JouluNet', 'juggler.jp', 'JustTrance', 'JxcelDolghmQ', 'K-FurRadio', 'KampungChat',
    'Kewl-Chat', 'Keyboard-Failure', 'KhClub', 'Kickchat', 'Klat00', 'Knet7.org', 'KnightIRC', 'Koach.Com', 'KoolIRC',
    'KottNet', 'KreyNet', 'krono.net', 'kyunet', 'ladysclub-irc', 'LagnetCrew', 'Langochat.net', 'LatinosChat',
    'Lazynet', 'LetsTalkCoding', 'LichtSnel', 'LinkBR', 'LinkNet', 'LipperChat', 'LizardIRC', 'looksharp', 'LostIRC',
    'LostSignal', 'Luatic', 'LucidChat', 'LUGS', 'MaddShark', 'MagicStar', 'MagNET', 'malkier', 'Malvager', 'MantraIRC',
    'MattsTech', 'MeFalcon', 'MegaLeak', 'Mibbit', 'Mimacy', 'MindForge', 'MIXXnet', 'MoepNet', 'MonsterIRC', 'Mozilla',
    'MsnIRC', 'MyIRCd', 'Mynet.bz', 'n00bstories', 'NandOX', 'NationCHAT', 'Necron', 'NekoNet', 'Neoturbine',
    'Nerdfighteria', 'nerdhacks', 'Netfuze', 'Netgoth', 'Netrusk', 'NeutralIRC', 'NewNet', 'NextIRC', 'nickreboot',
    'NightStar.Net', 'NiX', 'NixNodes', 'NixtrixIRC', 'NodNet.Ru', 'NordicIRC', 'Northfire', 'Noxether', 'Nullrewted',
    'Obsidian-IRC', 'OceanIRC', 'Oceanius', 'OFTC', 'OldChat', 'OltreIrc', 'OmegaIRC', 'Omerta', 'Omninet', 'Omofil',
    'OnlineGamesNet', 'OpenJoke', 'OperaNet', 'OptiLan', 'OSPnet', 'Otaku-IRC', 'OtakuIRC.DE', 'OtherNet',
    'OtherWorlders', 'OVERdrive-IRC', 'oz.org', 'Ozinger', 'OZIRC', 'P2P-NET', 'P2PChat', 'ParadoxIRC', 'paranode.net',
    'PC-Logix', 'PeerCommons', 'PenguinFriends', 'PhatNET', 'PhreikNET', 'phrenzyIRC', 'PieNet', 'PIK', 'Pilgerer',
    'PimpWar', 'PIRC.pl', 'place2chat.be', 'PlanetChat', 'PlatinumIRC', 'PolNet', 'PonyChat', 'portalx.org', 'PowaNet',
    'priyobd', 'Procrast.Net', 'PS3Sanctuary', 'PSIGenix', 'PsychoBabble', 'PTnet', 'PurpleSurge', 'QtalkID',
    'QuakeNet', 'QuartzNet', 'QuickFox', 'RadioClick.ro', 'RadioKlass', 'RagingFist.net', 'rc6', 'RealIRC',
    'ReaperSecurity', 'RebelNet', 'Recycled-IRC', 'Red-Orbita', 'RedeNorte', 'RediRC', 'RedLatina', 'ReichaNet',
    'RelaxedIRC', 'RelicNet', 'RevolSyS', 'RezoSup', 'RisposteInformatiche', 'Rizon', 'RizzitChat', 'RoIRC',
    'RootWorlD', 'RozzNet', 'rs2i', 'RusNet', 'SA-IRC', 'SameTeem', 'ScaryNet', 'SceneCritique', 'SceneP2P',
    'ScoutLink', 'SeersIRC', 'SeionIRC', 'SeniorenChat', 'Seraphim', 'Serenia', 'Serenity-IRC', 'ShadowFire',
    'ShadowNET', 'ShadowWorld.Net', 'ShakeIT', 'sierranet.org', 'silph', 'SimosNap', 'SkyIrc', 'SkyN3T', 'SKYROCK',
    'Slacknet', 'SlashNET', 'SmokeyNet', 'Smurfnet', 'Snoonet', 'Snyde', 'Sohbet.net', 'SolarNet', 'SolidIRC',
    'SomeNet', 'SorceryNet', 'SoundsNWaves.net', 'SoylentNews', 'SpaceTronix', 'Speak', 'Spiderchat', 'SpigotMC',
    'SpotChat', 'ST-City', 'Staff-Chat.net', 'StarChat', 'StarLink-IRC', 'StarLink.Org', 'StarNet', 'Station51.net',
    'StayNet', 'stne.net', 'StormBit', 'Striked', 'Subluminal', 'SurrealChat.net', 'sVipCHAT', 'SwiftIRC',
    'swissIRC.net', 'synIRC', 'SyrolNet', 'Taphouse', 'tawx', 'TChat-IRC', 'Tchat-IRC.Net', 'tchatville',
    'Team-Mondial', 'TeamIRC', 'Techtronix', 'Telefragged', 'TequilaMockingbird', 'Teranova', 'TheOneRing',
    'TodoChat.org', 'TransAdvice', 'Travian', 'TribalWar', 'TripSit', 'TrivialityZone', 'TrollIRC', 'TRSohbet',
    'TrYPNET', 'TsukiIRC', 'Tweakers', 'Twister-IRC', 'UK-IRC.net', 'UKChatBox', 'UKChatters', 'UnderMind', 'Undernet',
    'UnitedChat', 'UniversNet', 'UnovaRPGNet', 'UplinkIRC', 'Ustream', 'UtoNet', 'ValleyNode', 'Verque', 'vIRCio',
    'VirtuaLife', 'ViSiON-iRC', 'Volatile', 'VolimoNet', 'VoltIRC', 'VoodooNet', 'w3.org', 'WackyChat', 'WanaLike',
    'WARPRadioNetwork', 'WebcastsOnline', 'WebChat', 'WeNet', 'WhatNET', 'WhereWeBDSM', 'WikkedWire', 'WinBeta',
    'WonderNet', 'WorldAssault', 'WorldIRC', 'Worldnet', 'WorldzNet', 'Wotnet', 'WugNet', 'WUP', 'XChannel',
    'Xerologic', 'XeroMem', 'Xertion', 'Xevion.Net', 'XMission', 'XxXChatters', 'YakNet', 'yuppi-du.it', 'ZAIRC',
    'Zandronum', 'ZEnet', 'zeolia', 'ZeroNode', 'Zoite', 'Zurna.Net', 'zwergenirc.de'
];

$needleSet['ircNetworks'] = [
    'Vision', /* Should return Vision-irc */
    'otfc', /* oftc */
    'OfCourse', /* irc source, not important */
    'scary', /* scarynet */
    'NodeFree', /* freenode, fails on most ****** */
    'Free Node', /* freenode */
    'UnderNodeFreeNet', /* would like this to show undernet and freenode in first two results */
    'Undernet', /* exact match test... */
    'pt',
    'p2p', /* should show p2pchat, p2p-net in first 2 results, scenep2p at least in top 5 */
    'dal', /* dalnet */
    '-', /* doesn't matter just for kicks */
    'A quick brown fox jumped over the lazy dog' /* quickfox */
];

$dataSets['typesetTxt'] = [
    'A quick brown fox', 'jumped over', 'the lazy', 'dog',
    'qwertyuiop',
    'qwertyuiopasdfghjklzxcvbnm',
    'A quick brown fox jumped over the lazy dog',
    'A quick brown fox jumped over the lazy dog\'s back',
    'The wizard quickly jinxed the gnomes before they vaporized', 'Wizards are great',
    'Expect skilled signwriters to use many jazzy, quaint old alphabets effectively. ',
    'abc',
];

$needleSet['typesetTxt'] = [
    'hello',
    'brown fox',
    'lazy dog',
    'jump',
    'dog back',
    'abcdefghijklmnopqrstuvwxyz',
    'def'

];

$testFunctions = [
    'originalVersion'       => function ($needle, $haystack, $caseSensitive = true) {
        if(!$caseSensitive) {
            $needle = strtolower($needle);
        }

        $confidence_map = [];
        foreach ($haystack as $h) {
            if(!$caseSensitive) {
                $h = strtolower($h);
            }

            if(strlen($needle) < strlen($h)) {
                $normalize = strlen($needle);
            } else {
                $normalize = strlen($h);
            }

            $confidence_map[$h] = $normalize - (levenshtein($needle, $h) * 2);
            $confidence_map[$h] += similar_text($needle, $h);
        }

        //natsort($confidence_map);
        asort($confidence_map, SORT_NUMERIC);
        $confidence_map = array_reverse($confidence_map);

        return $confidence_map;
    },


    'debugVersion'          => function ($needle, array $haystack, $caseSensitive = true) {
        if(!$caseSensitive) {
            $needle = strtolower($needle);
        }

        $confidenceMap = [];
        foreach ($haystack as $h) {
            if(!$caseSensitive) {
                $h = strtolower($h);
            }

            //echo "Straw=$h\n";
            if(strlen($needle) < strlen($h)) {
                $normalize = strlen($needle);
            } else {
                $normalize = strlen($h);
            }

            $normalize = 0;

            //echo " > Normalize=$normalize\n";

            //$confidenceMap[$h] = $normalize - (levenshtein($needle, $h) * 2);
            $levenshtein = levenshtein($needle, $h);
            //echo " > Levensthein 1st Pass: $levenshtein\n";
            $levenshtein = ($levenshtein);
            //echo " > Levensthein 2nd Pass: $levenshtein\n";

            //$confidenceMap[$h] += similar_text($needle, $h);
            $similar_text = similar_text($needle, $h, $p);
            $p = round($p);
            //echo " > Similar text pass: " . $similar_text . "\n";

            $confidenceMap[$h] = $normalize - $levenshtein + $similar_text;
            $confidenceMap[$h] = $p;

            //echo " > Confidence Score: {$confidenceMap[$h]}\n";

            //echo "\n\n";


        }

        asort($confidenceMap, SORT_NUMERIC);
        $confidenceMap = array_reverse($confidenceMap);

        return $confidenceMap;
    },

    'levenshteinOnly'       => function ($needle, array $haystack, $caseSensitive = true) {
        if(!$caseSensitive) {
            $needle = strtolower($needle);
        }

        $confidenceMap = [];
        foreach ($haystack as $h) {
            if(!$caseSensitive) {
                $h = strtolower($h);
            }
            $levenshtein = levenshtein($needle, $h);
            $levenshtein = ($levenshtein);
            $confidenceMap[$h] = $levenshtein;
        }

        asort($confidenceMap, SORT_NUMERIC);

        #$confidenceMap = array_reverse($confidenceMap);

        return $confidenceMap;
    },

    'levenshteinNorm' => function ($needle, array $haystack, $caseSensitive = true) {
        if(!$caseSensitive) {
            $needle = strtolower($needle);
        }

        $confidenceMap = [];
        foreach ($haystack as $h) {
            if(!$caseSensitive) {
                $h = strtolower($h);
            }

            if(strlen($needle) < strlen($h)) {
                $normalize = strlen($needle);
            } else {
                $normalize = strlen($h);
            }

            $levenshtein = $normalize - levenshtein($needle, $h);
            $levenshtein = ($levenshtein);
            $confidenceMap[$h] = $levenshtein;
        }

        asort($confidenceMap, SORT_NUMERIC);
        $confidenceMap = array_reverse($confidenceMap);

        return $confidenceMap;
    },

    'similarTextOnly'       => function ($needle, array $haystack, $caseSensitive = true) {
        if(!$caseSensitive) {
            $needle = strtolower($needle);
        }

        $confidenceMap = [];
        foreach ($haystack as $h) {
            if(!$caseSensitive) {
                $h = strtolower($h);
            }
            $levenshtein = similar_text($needle, $h);
            $confidenceMap[$h] = $levenshtein;
        }

        asort($confidenceMap, SORT_NUMERIC);

        $confidenceMap = array_reverse($confidenceMap);

        return $confidenceMap;
    },

    'levPercent'            => function ($needle, array $haystack, $caseSensitive = true) {
        if(!$caseSensitive) {
            $needle = strtolower($needle);
        }

        $confidenceMap = [];
        foreach ($haystack as $h) {
            if(!$caseSensitive) {
                $h = strtolower($h);
            }

            $lev = levenshtein($needle, $h);
            $str1len = strlen($needle);
            $str2len = strlen($h);
            if($str1len < $str2len) {
                $pct = ($str1len - $lev) / $str1len;
            } else {
                $pct = ($str2len - $lev) / $str2len;
            }
            $pct = $pct * 100;
            $confidenceMap[$h] = round($pct);
        }
        asort($confidenceMap, SORT_NUMERIC);
        $confidenceMap = array_reverse($confidenceMap);

        return $confidenceMap;
    },
];

?>

    <!DOCTYPE html>
    <html>
    <head>
        <script src="http://use.edgefonts.net/source-code-pro.js"></script>
        <style>
            body {
                font-family: source-code-pro, Monaco, "Bitstream Vera Sans Mono", "Lucida Console", Terminal, monospace;
            }

            .dataResult {
                float: left;
                margin: 5px;
                border-right: 1px solid #ccc;
            }

            .dataResult td {
                font-size: 9pt;
            } </style>
        <title>searchFunc results</title>
    </head>
    <body>

<?php

foreach ($dataSets as $dIdx => $dataset) {
    $needles = $needleSet[$dIdx];

    if(empty($needles)) {
        die("No needles for dataset idx {$dIdx}");
    }

    echo '<h1>dataset=' . $dIdx . '</h1>';


    foreach ($needles as $n) {

        echo "<h2>needle=$n</h2>";

        foreach ($testFunctions as $funcName => $func) {

            $return = $func($n, $dataset, false);

            echo '<table class="dataResult">';
            echo '<tr><th colspan="2">' . $funcName . '</th></tr>';
            //echo '<tr><th>Score</th><th>Data</th></tr>';

            $showMax = 6; // 0 = show all

            $iter = 0;
            foreach ($return as $data => $score) {

                $maxLen = 40;
                $dataTitle = $data;
                if($maxLen > 0 && strlen($data) > $maxLen) {
                    $data = substr($data, 0, ($maxLen - 2)) . '..';
                    $truncPrefix = '<em>';
                    $truncSuffix = '</em>';
                } else {
                    $truncPrefix = '';
                    $truncSuffix = '';
                }

                echo '<tr><td>' . $score . '</td><td title="' . $dataTitle . '">' . $truncPrefix . $data . $truncSuffix . '</td></tr>';

                if($showMax != 0 && ++$iter >= $showMax) {
                    break;
                }

            }

            echo '</table>';
        }

        echo '<div style="clear: both">&nbsp;</div>';

        echo "\n";

    }

    echo "\n";

}

echo '</body></html>';