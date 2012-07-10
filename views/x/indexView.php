<?php
    include 'share/header.php';
    global $exfe_res;
?>
</head>

<body>
<?php
    include 'share/nav.php';
?>
<script>
    var AvailableBackgrounds = <?php echo json_encode($this->getVar('backgrounds')); ?>;
</script>

<!--
<script type="text/javascript" src="/static/?f=js/libs/showdown.js"></script>
<script type="text/javascript" src="/static/?f=js/libs/jquery.ba-outside-events.js"></script>
Exfee Widget
<link type="text/css" href="/static/?f=css/exfee.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>" rel="stylesheet">
<script src="/static/?f=js/apps/exfee.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
EXFE Maps
<link type="text/css" rel="stylesheet" href="/static/?f=css/maps.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>">
<script src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script src="/static/?f=js/apps/maps.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
-->

<!-- container -->
<div class="container">
<!--<header></header>-->

<div role="main">

  <section id="gather" class="x-gather">

    <!-- Cross-Form -->
    <div class="cross-form" style="display:none"> <!-- @todo @caifangdun move this style to css file -->

      <div class="form-horizontal">

          <legend class="hide">Extending form controls</legend>

          <!-- Cross-Form: Title -->
          <div class="control-group">
            <label class="control-label" for="gather-title">Intent</label>
            <div class="controls">
              <input type="text" class="input-xlarge" id="gather-title" placeholder="Enter intent" />
            </div>
          </div>
          <!-- .Cross-Form: Title -->

          <!-- Cross-Form: Exfee -->
          <div class="control-group cross-exfee" id="gather-exfee">
            <label class="control-label" for="gather-exfee-input">Exfee</label>
            <div class="controls">
              <input type="text" class="input-xlarge" id="gather-exfee-input" placeholder="Enter attendees' information" />
            </div>

            <div class="autocomplete" id="gather-exfee-complete"><ol></ol></div>

            <div class="pull-right cross-stats">
              <span class="attended"></span>
              <div class="confirmed">
                <div class="total"></div>
                <div>confirmed</div>
              </div>
            </div>

            <div class="cross-identities">
              <ul class="thumbnails">
              </ul>
            </div>

          </div>
          <!-- .Cross-Form: Exfee -->

          <div class="form-actions">
            <div class="pull-right control-buttos">
              <button class="btn" id="cross-form-discard">Discard</button>
              <button class="btn btn-primary" id="cross-form-gather">Gather</button>
            </div>
            <div>
              <p>Edit more information directly in preview below.</p>
            </div>
        </div>

      </div>

    </div>

    <!-- Cross-background -->
    <div class="cross-background">
      <div class="cross-background-blur"></div>
      <div class="cross-background-side"></div>
    </div>

    <!-- Cross-Container -->
    <div class="cross-container">

      <!-- Cross-Shuffle-Background -->
      <button class="shuffle-background">Shuffle Background</button>
      <!-- .Cross-Shuffle-Background -->

      <!-- Cross-Title -->
      <div class="cross-title" editarea="title">
        <h1 class="show"></h1>
        <textarea class="edit" style="display:none"></textarea> <!-- @todo @caifangdun move this style to css file -->
      </div>

      <div class="row">

        <!-- gr-b -->
        <div class="gr-a">

          <!-- Cross-Content -->
          <div class="cross-description" editarea="description">
            <div class="show"></div>
            <textarea class="edit" style="display:none"></textarea> <!-- @todo @caifangdun move this style to css file -->
          </div>

          <!-- Cross-rsvp -->
          <div class="cross-rsvp">

          </div>

          <!-- Cross-Conversation -->
          <div class="cross-conversation" style="display:none"> <!-- @todo @caifangdun move this style to css file -->

            <a href="#" class="pull-right cross-history">Hide history</a>

            <h3>Conversation</h3>

            <!-- Cross-Form -->
            <div class="avatar-comment" id="conversation-form">
              <span class="pull-left avatar">
                <img alt="" src="" width="40" height="40" />
              </span>

              <div class="comment">
                <div class="comment-form">
                  <textarea></textarea>
                </div>
              </div>
            </div>
            <!-- .Cross-Form -->


            <!-- Conversation-Timeline -->
            <div class="conversation-timeline">
            </div>
            <!-- .Conversation-Timeline -->

          </div>

        </div>
        <!-- .gr-b -->

        <!-- gr-b -->
        <div class="gr-b">
          <div>

            <!-- Cross-Time -->
            <div class="cross-dp cross-date" editarea="time">
              <h2></h2>
              <div class="cross-time show"></div>
              <input type="text" class="edit" placeholder="1984-06-04 09:00 AM" style="display:none"> <!-- @todo @caifangdun move this style to css file -->
            </div>

            <!-- Cross-Place -->
            <div class="cross-dp cross-place" editarea="place">
              <h2 class="show"></h2>
              <address class="show"></address>
              <textarea class="edit" style="display:none"></textarea> <!-- @todo @caifangdun move this style to css file -->
            </div>

            <!-- Cross-Map
            <div class="cross-map">
              <!--<img src="http://maps.googleapis.com/maps/api/staticmap?center=40.714728,-73.998672&markers=size:mid%7Ccolor:blue%7C40.714728,-73.998672&zoom=13&size=280x140&maptype=road&sensor=false" alt="" />
              <img src="/img/google-staticmap.png" alt="" width="280px" height="140px" />
            </div>
            -->

            <!-- Cross-Form: Exfee -->
            <div class="control-group cross-exfee" id="cross-exfee">
              <label class="control-label" for="cross-exfee-input">Exfee</label>
              <div class="controls">
                <input type="text" class="input-xlarge" id="cross-exfee-input" placeholder="Enter attendees' information" />
              </div>

              <div class="autocomplete" id="cross-exfee-complete"><ol></ol></div>

              <div class="pull-right cross-stats">
                <span class="attended"></span>
                <div class="confirmed">
                  <div class="total"></div>
                  <div>confirmed</div>
                </div>
              </div>

              <div class="cross-identities">
                <ul class="thumbnails">
                </ul>
              </div>

            </div>
            <!-- .Cross-Form: Exfee -->

          </div>
        </div>
        <!-- .gr-b -->


      </div>
    </div>

  </section>
</div>

<!--<footer></footer>-->
</div>

<!-- JavaScript at the bottom for fast page loading -->
<?php include 'share/footer.php'; ?>
<script src="/static/js/marked/0.2.5/marked.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
<script src="/static/_cross.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
<script src="/static/js/userpanel/0.0.1/userpanel.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

</body>
</html>

