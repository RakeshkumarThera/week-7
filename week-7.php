<!doctype html>
<html>
  <head>
    <title>Socket.IO chat</title>
    <style>
      * { margin: 0; padding: 0; box-sizing: border-box; }
      body { font: 13px Helvetica, Arial; background-color:#c2d6d6;}
      form { background: #000; padding: 3px; position: fixed; bottom: 0; width: 100%; }
      form input { border: 0; padding: 10px; width: 90%; margin-right: .5%; }
      form button { width: 9%; background: rgb(130, 224, 255); border: none; padding: 10px; }
      #messages { list-style-type: none; margin: 0; padding: 0; }
      #messages li { padding: 5px 10px; }
      #messages li:nth-child(odd) { background: #eee; }
	  #typing{float:bottom;}
	  #onlineUsers{font-size:13px;}
	  #onlineUsers1{font-size:13pxpx; font-color:#476b6b}    
  </style>
  </head>
  <body>
        <div>
	<div style="float:left;width:10%">
	<p id="onlineUsers1"> Online Users:</p>
		<div id="onlineUsers"></div>
	</div>
	<div style="float:right;width:90%">        
	<ul id="messages"></ul>
	<div id="typing"></div>
        </div>
	</div>
    <form action="">
      <input id="m" autocomplete="off" /><button>Send</button>
    </form>
	<!--Read ME for getting details about the CryptoJS import:https://code.google.com/archive/p/crypto-js/ -->
	<script type="text/javascript" language="javascript" src="http://cryptojs.altervista.org/cryptojs/crypto-cryptojs/aes-v3/aes.js"></script>
	<script type="text/javascript" language="javascript" src="http://cryptojs.altervista.org/cryptojs/crypto-cryptojs/aes-v3/pbkdf2.js"></script>
	<script src="https://cdn.socket.io/socket.io-1.2.0.js"></script>
    <script src="https://code.jquery.com/jquery-1.11.1.js"></script>
	<script>
      var currentUser = "";
      var socket = io();
	  
	  var salt = "961531a469ae307ecba504d9218fc3dbfd78a104c602b47654fc67dd285895c9cecd040e6c61c7ca1092e7df6bc45431bc62c1c9562b32517916f4302f2d9133fd7400f18404f3e41772a0e58c1bd052d0dfe7359ac711271026786f961b35100ed9358dc620a57d91da84d78c92911f7adc6841f066b0a7433c9607f382263b"; //To fix error {Cannot read salt of undefined}
	  var key = CryptoJS.PBKDF2("123", salt, { keySize: 256 / 32, iterations: 1000 })
	  
	  var jsonFormatter = {
		stringify: function (cipherParams) {
        var jsonObj = {
            ct: cipherParams.ciphertext.toString(CryptoJS.enc.Base64)
        };
		// As an option add iv and salt.
        if (cipherParams.iv) {
            jsonObj.iv = cipherParams.iv.toString();
        }

        return JSON.stringify(jsonObj);
		},

		parse: function (jsonStr) {
        var jsonObj = JSON.parse(jsonStr);

        // Extract ciphertext from json object, and create cipher params object.
        var cipherParams = CryptoJS.lib.CipherParams.create({
            ciphertext: CryptoJS.enc.Base64.parse(jsonObj.ct)
        });
		// As an option extract iv and salt.
        if (jsonObj.iv) {
            cipherParams.iv = CryptoJS.enc.Hex.parse(jsonObj.iv);
        }
        return cipherParams;
		}
	};
	  
	  $('form').submit(function(){
		var msg = $('#m').val(); 
		var iv = CryptoJS.lib.WordArray.random(128 / 8);
		var encrypted = CryptoJS.AES.encrypt(msg, key, { iv:iv });
		var encryptedString = jsonFormatter.stringify(encrypted);
		console.log("Encodedstrig:"+encryptedString);
        socket.emit('chat message', encryptedString);  
		$('#m').val('');
        return false;
      });
	  
	  $("#m").keydown(function() {
	   socket.emit('typing1');
	  });
	  
	  $("#m").keyup(function() {
		//Setting a delay of 2 seconds for this function. 
		setTimeout(function(){
		socket.emit('stoptyping');
		}, 2000 );
	  });
	  
	  socket.on('chat message', function(msg){
		var originalMsg ;
		if(msg.name != "server"){
			var encrypted = jsonFormatter.parse(msg.text);
			//console.log(encrypted);
			var decrypted = CryptoJS.AES.decrypt(encrypted, key, { iv: encrypted.iv });
			originalMsg = decrypted.toString(CryptoJS.enc.Utf8);
			$('#messages').append($('<li>').text(msg.name+"::"+originalMsg));
			console.log("Decrypted Text:"+originalMsg);
		}else{
			originalMsg = msg.text;
			$('#messages').append($('<li>').text(originalMsg));
		}
	  });
	  
	  socket.on('typing1', function(msg){
		$('#typing').html(msg);
      });
	  
	  socket.on('stoptyping', function(){
		 $('#typing').html("");
      });
	  
	  socket.on('connect', function(){
	  currentUser = prompt("What's your name?");
        // Call the server-side function 'adduser' and send one parameter (value of prompt)
        socket.emit('adduser', currentUser);
	});
	//This method is called to show the online users.
	  socket.on('showOnlineUsers', function(allUsers){
	  $('#onlineUsers').html("");
	  $.each(allUsers, function(key, value) {
			if(key!=currentUser)
			$('#onlineUsers').append('<div style="cursor:pointer;" onclick="send_individual_msg(\''+value+'\')">' + key + '</div>');
			else
			$('#onlineUsers').append('<div><i>' + key + '</i></div>');
		});
      });
	  socket.on('msg_user_handle', function (username, data) {
		var msg = username+"::"+data;
		$('#messages').append($('<li>').text(msg));			
	});
	  //This is for private chat. 
	  function send_individual_msg(id)
	 {
		socket.emit('msg_user',id,prompt("Type your message:"));
	 }
	</script>
  </body>
</html>