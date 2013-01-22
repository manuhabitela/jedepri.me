        </div>

        <!--<div id="konami" class="no-mobile">↑&nbsp;&nbsp;↑&nbsp;&nbsp;↓&nbsp;&nbsp;↓&nbsp;&nbsp;←&nbsp;&nbsp;→ …</div>-->
        
        <script>
            var _gaq=[['_setAccount','<?php echo APP_ANALYTICS ?>'],['_trackPageview']];
            (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
            g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
            s.parentNode.insertBefore(g,s)}(document,'script'));
        </script>
        <!-- dev : /js/script.js -->
        <?php $js = PROD ? '/js/script.min.js?v=58797562341' : '/js/script.js?v='.time() ?>
        <!--[if gte IE 8]><!--> <script src="<?php echo $js ?>"></script> <!--<![endif]-->
    </body>
</html>
