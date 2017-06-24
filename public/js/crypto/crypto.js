/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */
 
var Crypto = {
	encrypt: function(msg, secret) {
		// Generate salt
		var salt = CryptoJS.lib.WordArray.random(128/8).toString().substring(0,16); // 16 Bytes

		// Generate IV
		var iv = CryptoJS.lib.WordArray.random(128/8) // 16 Bytes

		// Generate key
		var key = CryptoJS.PBKDF2(secret, salt, {
			keySize: 256/32,
			iterations: 2048,
			hasher: CryptoJS.algo.SHA1
		});

		// Encrypt
		var encrypted = CryptoJS.AES.encrypt(msg, key, {
			iv: iv,
			padding: CryptoJS.pad.Pkcs7,
			mode: CryptoJS.mode.CBC
		});

		// Encode
		var encoded = btoa(encrypted.toString() + ":" + CryptoJS.enc.Base64.stringify(iv) + ":" + salt);
		return encoded
	},

	decrypt: function(encryptedString, secret) {
		// Decode
		var rawData = atob(encryptedString);
		var rawPieces = rawData.split(":");

		// Extract payload
		var crypttext = rawPieces[0];

		// Extract IV
		var iv = CryptoJS.enc.Base64.parse(rawPieces[1]);
		//var iv = CryptoJS.enc.Utf8.parse("1234567890123456");

		// Extract salt
		var salt = rawPieces[2];

		// Generate key - keySize is key-length / word-length (32bit; CryptoJS works with words)
		var key256Bits  = CryptoJS.PBKDF2(secret, salt, { keySize: 256/32, iterations: 2048, hasher: CryptoJS.algo.SHA1 });

		// Init cipher
		var cipherParams = CryptoJS.lib.CipherParams.create({ciphertext: CryptoJS.enc.Base64.parse(crypttext)});

		// Decrypt
		var plaintextArray = CryptoJS.AES.decrypt(
		  cipherParams,
		  key256Bits,
		  { iv: iv }
		);

		// Encode
		return CryptoJS.enc.Utf8.stringify(plaintextArray);
	}
}