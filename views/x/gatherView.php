<?php include "share/header.php"; ?>
<body>
<?php include "share/nav.php"; ?>
<h3>Gather for your X</h3>
<form action="" method="post">
<label>Title:</label><input type="text"  name="title" id="title" class="inputText"/><br/>
<label>Description:</label><input type="text"  name="description" id="description" class="inputText"/><br/>
<label>Date & time:</label><input type="text"  name="datetime" id="datetime" class="inputText"/><br/>
<label>place:</label><textarea name="place" id="place"></textarea><br/>
<label>exfee:</label><input type="text"  name="exfee" id="exfee" clas="inputText"/><br/>
<input id="user_submit" name="commit" type="submit" value="Submit" />
</form>
</body>
</html>
