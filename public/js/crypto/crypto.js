/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var Crypto = {
	blockSize: 128,
	keySize: 256,

	encrypt: function(msg, secret) {
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
		return btoa(
			atob(CryptoJS.enc.Base64.stringify(iv)) +
			atob(CryptoJS.enc.Base64.stringify(salt)) +
			atob(encrypted.toString())
		);
	},

	/* keySize is (key-length / word-length) (32bit; CryptoJS works with words) */
	generateKey: function(secret, salt) {
		return CryptoJS.PBKDF2(
			secret,
			salt,
			{
				keySize: this.keySize / 32,
				iterations: 2048,
				hasher: CryptoJS.algo.SHA1
			}
		);
	},

	decrypt: function(encryptedString, secret) {
		// Decode
		var raw = CryptoJS.enc.Base64.parse(encryptedString);

		// Extract IV
		var iv = CryptoJS.lib.WordArray.create(raw.words.slice(0, this.blockSize / 32));

		// Extract Salt
		var salt = CryptoJS.lib.WordArray.create(raw.words.slice(this.blockSize / 32, this.blockSize / 32 + this.blockSize / 32));

		// Extract ciphertext
		var ciphertext = CryptoJS.lib.WordArray.create(raw.words.slice(this.blockSize / 32 + this.blockSize / 32));

		// Generate key
		var key = this.generateKey(secret, salt);

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
	}
}