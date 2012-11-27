<!DOCTYPE html>
<!--[if lt IE 7]>      <html lang="fr" class="lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html lang="fr" class="lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html lang="fr" class="lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="fr" class=""> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Je déprime mais je veux arrêter, je suis en pleine dépression et ça m'emmerde : heureusement, jedepri.me est là</title>
        <meta name="description" content="Un petit coup de déprime ? Allez viens.">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- dev : /css/style.css -->
        <link rel="stylesheet" href="/css/style.css?v=12s34567">
    </head>
    <body>

        <!--[if lte IE 7]>
            <p class="obsolete-browser">Vous utilisez un navigateur <strong>obsolète</strong>. <a href="http://browsehappy.com/">Mettez-le à jour</a> pour naviguer sur Internet de façon <strong>sécurisée</strong> !</p>
        <![endif]-->

        <a href="#" id="options-toggler">Options</a>

        <div id="options" class="invisible">
            <form method=post>
                <p>Que te faut-il pour arrêter de déprimer ?</p>
                <ul>
                    <li><label><input checked="checked" type="checkbox" name="category[]" value="animals">Des animaux</label></li>
                    <li><label><input checked="checked" type="checkbox" name="category[]" value="fun">Du fun <span class="note">en anglais</span></label></li>
                    <li><label><input checked="checked" type="checkbox" name="category[]" value="gifs">Des gifs <span class="note">là aussi, en anglais</span></label></li>
                    <li><label><input type="checkbox" name="category[]" value="girls">Des madames</label></li>
                    <li><label><input type="checkbox" name="category[]" value="guys">Des monsieurs</label></li>
                </ul>
                <button>Valider</button>
            </form>
        </div>
        <div id="content">
        <?php echo $content_for_layout ?> 
        </div>

        <!--<div id="konami" class="no-mobile">↑&nbsp;&nbsp;↑&nbsp;&nbsp;↓&nbsp;&nbsp;↓&nbsp;&nbsp;←&nbsp;&nbsp;→ …</div>-->
        
        <script>
            var _gaq=[['_setAccount','UA-13184829-3'],['_trackPageview']];
            (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
            g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
            s.parentNode.insertBefore(g,s)}(document,'script'));
        </script>
        <!-- dev : /js/script.js -->
        <!--[if gte IE 8]><!--> <script src="/js/script.js?v=z54321"></script> <!--<![endif]-->
    </body>
</html>
