/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var Crypto = {
	blockSize: 128,
	keySize: 256,
	iterations: 2048,

	encrypt: function(msg, secret, sign) {
		// Generate IV (16 Bytes)
		var iv = CryptoJS.lib.WordArray.random(this.blockSize / 8);

		// Generate salt (16 Bytes)
		var salt = CryptoJS.lib.WordArray.random(this.blockSize / 8);

		// Generate key
		var key = this.generateKey(secret, salt);

		// Encrypt
		var encrypted = CryptoJS.AES.encrypt(
			msg,
			key,
			{
				iv: iv,
				padding: CryptoJS.pad.Pkcs7,
				mode: CryptoJS.mode.CBC
			}
		);

		// Encode (iv + salt + payload)
		var ciphertext64 = this.base64UrlEncode(
			atob(CryptoJS.enc.Base64.stringify(iv)) +
			atob(CryptoJS.enc.Base64.stringify(salt)) +
			atob(encrypted.toString())
		);

		// Sign
		if (sign) {
			ciphertext64 = ciphertext64 + ":" + this.sign(ciphertext64, key);
		}

		return ciphertext64;
	},

	sign: function(str, key) {
		return CryptoJS.HmacSHA256(str, key);
	},

	/* keySize is (key-length / word-length) (32bit; CryptoJS works with words) */
	generateKey: function(secret, salt) {
		return CryptoJS.PBKDF2(
			secret,
			salt,
			{
				keySize: this.keySize / 32,
				iterations: this.iterations,
				hasher: CryptoJS.algo.SHA1
			}
		);
	},

	decrypt: function(encryptedString, secret) {
		// Separate payload from potential hmac
		var separated = encryptedString.trim().split(":");

		// Extract HMAC if signed
		var hmac = (separated[1]) ? separated[1] : "";

		// Decode
		var raw = this.base64UrlDecode(separated[0]);

		// Extract IV
		var iv = CryptoJS.lib.WordArray.create(raw.words.slice(0, this.blockSize / 32));

		// Extract Salt
		var salt = CryptoJS.lib.WordArray.create(raw.words.slice(this.blockSize / 32, this.blockSize / 32 + this.blockSize / 32));

		// Extract ciphertext
		var ciphertext = CryptoJS.lib.WordArray.create(raw.words.slice(this.blockSize / 32 + this.blockSize / 32));

		// Generate key
		var key = this.generateKey(secret, salt);

		if (hmac && !(this.sign(separated[0], key) == hmac)) {
			return null;
		}

		// Init cipher
		var cipherParams = CryptoJS.lib.CipherParams.create({ciphertext: ciphertext});

		// Decrypt
		var plaintextArray = CryptoJS.AES.decrypt(
		  cipherParams,
		  key,
		  {iv: iv}
		);

		// Encode
		return CryptoJS.enc.Utf8.stringify(plaintextArray);
	},

	base64UrlEncode: function(str) {
		return btoa(str).replace(/\+/g, '-').replace(/\//g, '_');
	},

	base64UrlDecode: function(str) {
		return CryptoJS.enc.Base64.parse(str.replace(/\-/g, '+').replace(/\_/g, '/'));
	},

	initAlphabet: function(uppercase, lowercase, numbers, specials) {
		var alphabet = "";

		if (uppercase) {
			alphabet += "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		}
		if (lowercase) {
			alphabet += "abcdefghijklmnopqrstuvwxyz";
		}
		if (numbers) {
			alphabet += "0123456789";
		}
		if (specials) {
			alphabet += "!§$%&/()=?.-;:_";
		}

		return alphabet;
	},

	randomString: function(uppercase, lowercase, numbers, specials, length) {
		var alphabet = this.initAlphabet(uppercase, lowercase, numbers, specials);
		var str = "";

		if (length > 0 && alphabet.length > 0) {
			for (var i = 0; i < length; i++) {
				var rand = Math.floor(Math.random() * (alphabet.length));
				str += alphabet[rand];
			}
			return str;
		}

		return "";
	},

	sha1: function(string) {
		var hash = CryptoJS.SHA1(string);
		return CryptoJS.enc.Hex.stringify(hash);
	}
}
