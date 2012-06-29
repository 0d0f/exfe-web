<?php
    $page = 'cross';
    include 'share/header.php';
    global $exfe_res;
?>
</head>

<body>
<?php
    include 'share/nav.php';
?>
<script>
    var
</script>


<script type="text/javascript" src="/static/?f=js/libs/showdown.js"></script>
<script type="text/javascript" src="/static/?f=js/libs/jquery.ba-outside-events.js"></script>
<!-- Exfee Widget -->
<link type="text/css" href="/static/?f=css/exfee.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>" rel="stylesheet">
<script src="/static/?f=js/apps/exfee.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
<!-- EXFE Maps -->
<link type="text/css" rel="stylesheet" href="/static/?f=css/maps.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>">
<script src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script src="/static/?f=js/apps/maps.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

<script src="/static/maps.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

<!-- container -->
<div class="container">
<!--<header></header>-->

<div role="main">

  <section id="gather" class="x-gather">

    <!-- Cross-Form -->
    <div class="cross-form">

      <form class="form-horizontal">

        <fieldset>
          <legend class="hide">Extending form controls</legend>

          <!-- Cross-Form: Title -->
          <div class="control-group">
            <label class="control-label" for="gather-title">Title</label>
            <div class="controls">
              <input type="text" class="input-xlarge" id="gather-title" placeholder="Edit title here" />
            </div>
          </div>
          <!-- .Cross-Form: Title -->

          <!-- Cross-Form: Description
          <div class="control-group">
            <label class="control-label" for="gather-description">Description</label>
            <div class="controls">
              <input type="text" class="input-xlarge" id="gather-description" placeholder="Write some words about this X." />
            </div>
          </div>
          .Cross-Form: Description -->

          <!-- Cross-Form: Date & Time
          <div class="control-group">
            <label class="control-label" for="gather-time">Date & Time</label>
            <div class="controls">
              <input type="text" class="input-xlarge" id="gather-time" placeholder="Sometime" />
            </div>
          </div>
          .Cross-Form: Date & Time -->

          <!-- Cross-Form: Location
          <div class="control-group">
            <label class="control-label" for="gather-place">Location</label>
            <div class="controls">
              <input type="text" class="input-xlarge" id="gather-place" placeholder="Somewhere" />
            </div>
          </div>
          .Cross-Form: Location -->

          <!-- Cross-Form: Host By
          <div class="control-group">
            <label class="control-label" for="gather-hostby">Host By</label>
            <div class="controls">
              <input type="text" class="input-xlarge" id="gather-hostby" />
            </div>
          </div>
          .Cross-Form: Host By -->

          <!-- Cross-Form: Exfee -->
          <div class="control-group">
            <label class="control-label" for="gather-exfee">Exfee</label>
            <div class="controls">
              <input type="text" class="input-xlarge" id="gather-exfee" placeholder="Enter attendees' information" />
            </div>
          </div>
          <!-- .Cross-Form: Exfee -->

          <!-- Cross-Form: Privacy
          <div class="control-group">
            <div class="control-label">Privacy</div>
            <div class="controls">
              <p>This is a private <span>X</span>. </p>
              <p>Sorry, public X is not supported yet, we're still working on it.</p>
            </div>
          </div>
          .Cross-Form: Privacy -->

          <div class="form-actions">
            <div class="pull-right control-buttos">
              <button class="btn">Discard</button>
              <button class="btn btn-primary" type="submit">Submit</button>
            </div>
            <div>
              <p><span>EXFE</span> for iPhone, keep everything on track.</p>
            </div>
        </div>

        </fieldset>

      </form>

    </div>

    <!-- Cross-background -->
    <div class="cross-background">
      <div class="cross-background-blur"></div>
      <div class="cross-background-side"></div>
    </div>

    <!-- Cross-Container -->
    <div class="cross-container">
      <!-- Cross-Title -->
      <div class="cross-title">
        <h1>Team dinner in San Francisco with Bay Area friends</h1>
      </div>

      <div class="row">

        <!-- gr-b -->
        <div class="gr-a">

          <!-- Cross-Content -->
          <div class="cross-description">
<p>
h: 680px;
e6e6e6
fafafa

2 2 0.25

315

70 88 100
0~1, h 377
</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, set eiusmod tempor incidunt et labore et dolore magna aliquam. Ut enim ad minim veniam, quis nostrud exerc. Irure dolor in reprehend incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
            <p>Duis aute irure dolor in reprehenderit in voluptate velit esse molestaie cillum. Tia non ob ea soluad incom dereud facilis est er expedit distinct. Nam liber te conscient to factor tum poen legum odioque civiuda et tam. Neque pecun modut est neque nonor et imper ned libidig met, consectetur adipiscing elit, sed ut labore et dolore magna aliquam is nostrud exercitation ullam mmodo consequet.</p>
          </div>

          <!-- Cross-rsvp -->
          <div class="cross-rsvp">

          </div>

        </div>
        <!-- .gr-b -->

        <!-- gr-b -->
        <div class="gr-b">
          <div>

            <!-- Cross-Time -->
            <div class="cross-dp cross-date">
              <h2>Tomorrow</h2>
              <div class="cross-time">6:30PM on Fri, Apr 8</div>
            </div>

            <!-- Cross-Place -->
            <div class="cross-dp cross-place">
              <h2>Crab House Pier 39, 2nd floor</h2>
              <address>Pier 39, 203 C<br /> San Francisco<br /> http://crabhouse39.com<br /> (555) 434-2722<br /> overflow: hidden;</address>
            </div>

            <!-- Cross-Map -->
            <div class="cross-map">
              <!--<img src="http://maps.googleapis.com/maps/api/staticmap?center=40.714728,-73.998672&markers=size:mid%7Ccolor:blue%7C40.714728,-73.998672&zoom=13&size=280x140&maptype=road&sensor=false" alt="" />-->
              <img src="/img/google-staticmap.png" alt="" width="280px" height="140px" />
            </div>

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
<!-- @todo -->
<!-- @todo -->
<!-- @todo -->

</body>
</html>

