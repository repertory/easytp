define(function() {
	//---------------------------------------------------------------------
// QRCode for JavaScript
//
// Copyright (c) 2009 Kazuhiko Arase
//
// URL: http://www.d-project.com/
//
// Licensed under the MIT license:
//   http://www.opensource.org/licenses/mit-license.php
//
// The word "QR Code" is registered trademark of
// DENSO WAVE INCORPORATED
//   http://www.denso-wave.com/qrcode/faqpatent-e.html
//
//---------------------------------------------------------------------

//---------------------------------------------------------------------
// QR8bitByte
//---------------------------------------------------------------------

	function QR8bitByte(data) {
		this.mode = QRMode.MODE_8BIT_BYTE;
		this.data = data;
	}

	QR8bitByte.prototype = {

		getLength : function(buffer) {
			return this.data.length;
		},

		write : function(buffer) {
			for (var i = 0; i < this.data.length; i++) {
				// not JIS ...
				buffer.put(this.data.charCodeAt(i), 8);
			}
		}
	};

//---------------------------------------------------------------------
// QRCode
//---------------------------------------------------------------------

	function QRCode(typeNumber, errorCorrectLevel) {
		this.typeNumber = typeNumber;
		this.errorCorrectLevel = errorCorrectLevel;
		this.modules = null;
		this.moduleCount = 0;
		this.dataCache = null;
		this.dataList = new Array();
	}

	QRCode.prototype = {

		addData : function(data) {
			var newData = new QR8bitByte(data);
			this.dataList.push(newData);
			this.dataCache = null;
		},

		isDark : function(row, col) {
			if (row < 0 || this.moduleCount <= row || col < 0 || this.moduleCount <= col) {
				throw new Error(row + "," + col);
			}
			return this.modules[row][col];
		},

		getModuleCount : function() {
			return this.moduleCount;
		},

		make : function() {
			// Calculate automatically typeNumber if provided is < 1
			if (this.typeNumber < 1 ){
				var typeNumber = 1;
				for (typeNumber = 1; typeNumber < 40; typeNumber++) {
					var rsBlocks = QRRSBlock.getRSBlocks(typeNumber, this.errorCorrectLevel);

					var buffer = new QRBitBuffer();
					var totalDataCount = 0;
					for (var i = 0; i < rsBlocks.length; i++) {
						totalDataCount += rsBlocks[i].dataCount;
					}

					for (var i = 0; i < this.dataList.length; i++) {
						var data = this.dataList[i];
						buffer.put(data.mode, 4);
						buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber) );
						data.write(buffer);
					}
					if (buffer.getLengthInBits() <= totalDataCount * 8)
						break;
				}
				this.typeNumber = typeNumber;
			}
			this.makeImpl(false, this.getBestMaskPattern() );
		},

		makeImpl : function(test, maskPattern) {

			this.moduleCount = this.typeNumber * 4 + 17;
			this.modules = new Array(this.moduleCount);

			for (var row = 0; row < this.moduleCount; row++) {

				this.modules[row] = new Array(this.moduleCount);

				for (var col = 0; col < this.moduleCount; col++) {
					this.modules[row][col] = null;//(col + row) % 3;
				}
			}

			this.setupPositionProbePattern(0, 0);
			this.setupPositionProbePattern(this.moduleCount - 7, 0);
			this.setupPositionProbePattern(0, this.moduleCount - 7);
			this.setupPositionAdjustPattern();
			this.setupTimingPattern();
			this.setupTypeInfo(test, maskPattern);

			if (this.typeNumber >= 7) {
				this.setupTypeNumber(test);
			}

			if (this.dataCache == null) {
				this.dataCache = QRCode.createData(this.typeNumber, this.errorCorrectLevel, this.dataList);
			}

			this.mapData(this.dataCache, maskPattern);
		},

		setupPositionProbePattern : function(row, col)  {

			for (var r = -1; r <= 7; r++) {

				if (row + r <= -1 || this.moduleCount <= row + r) continue;

				for (var c = -1; c <= 7; c++) {

					if (col + c <= -1 || this.moduleCount <= col + c) continue;

					if ( (0 <= r && r <= 6 && (c == 0 || c == 6) )
						|| (0 <= c && c <= 6 && (r == 0 || r == 6) )
						|| (2 <= r && r <= 4 && 2 <= c && c <= 4) ) {
						this.modules[row + r][col + c] = true;
					} else {
						this.modules[row + r][col + c] = false;
					}
				}
			}
		},

		getBestMaskPattern : function() {

			var minLostPoint = 0;
			var pattern = 0;

			for (var i = 0; i < 8; i++) {

				this.makeImpl(true, i);

				var lostPoint = QRUtil.getLostPoint(this);

				if (i == 0 || minLostPoint >  lostPoint) {
					minLostPoint = lostPoint;
					pattern = i;
				}
			}

			return pattern;
		},

		createMovieClip : function(target_mc, instance_name, depth) {

			var qr_mc = target_mc.createEmptyMovieClip(instance_name, depth);
			var cs = 1;

			this.make();

			for (var row = 0; row < this.modules.length; row++) {

				var y = row * cs;

				for (var col = 0; col < this.modules[row].length; col++) {

					var x = col * cs;
					var dark = this.modules[row][col];

					if (dark) {
						qr_mc.beginFill(0, 100);
						qr_mc.moveTo(x, y);
						qr_mc.lineTo(x + cs, y);
						qr_mc.lineTo(x + cs, y + cs);
						qr_mc.lineTo(x, y + cs);
						qr_mc.endFill();
					}
				}
			}

			return qr_mc;
		},

		setupTimingPattern : function() {

			for (var r = 8; r < this.moduleCount - 8; r++) {
				if (this.modules[r][6] != null) {
					continue;
				}
				this.modules[r][6] = (r % 2 == 0);
			}

			for (var c = 8; c < this.moduleCount - 8; c++) {
				if (this.modules[6][c] != null) {
					continue;
				}
				this.modules[6][c] = (c % 2 == 0);
			}
		},

		setupPositionAdjustPattern : function() {

			var pos = QRUtil.getPatternPosition(this.typeNumber);

			for (var i = 0; i < pos.length; i++) {

				for (var j = 0; j < pos.length; j++) {

					var row = pos[i];
					var col = pos[j];

					if (this.modules[row][col] != null) {
						continue;
					}

					for (var r = -2; r <= 2; r++) {

						for (var c = -2; c <= 2; c++) {

							if (r == -2 || r == 2 || c == -2 || c == 2
								|| (r == 0 && c == 0) ) {
								this.modules[row + r][col + c] = true;
							} else {
								this.modules[row + r][col + c] = false;
							}
						}
					}
				}
			}
		},

		setupTypeNumber : function(test) {

			var bits = QRUtil.getBCHTypeNumber(this.typeNumber);

			for (var i = 0; i < 18; i++) {
				var mod = (!test && ( (bits >> i) & 1) == 1);
				this.modules[Math.floor(i / 3)][i % 3 + this.moduleCount - 8 - 3] = mod;
			}

			for (var i = 0; i < 18; i++) {
				var mod = (!test && ( (bits >> i) & 1) == 1);
				this.modules[i % 3 + this.moduleCount - 8 - 3][Math.floor(i / 3)] = mod;
			}
		},

		setupTypeInfo : function(test, maskPattern) {

			var data = (this.errorCorrectLevel << 3) | maskPattern;
			var bits = QRUtil.getBCHTypeInfo(data);

			// vertical
			for (var i = 0; i < 15; i++) {

				var mod = (!test && ( (bits >> i) & 1) == 1);

				if (i < 6) {
					this.modules[i][8] = mod;
				} else if (i < 8) {
					this.modules[i + 1][8] = mod;
				} else {
					this.modules[this.moduleCount - 15 + i][8] = mod;
				}
			}

			// horizontal
			for (var i = 0; i < 15; i++) {

				var mod = (!test && ( (bits >> i) & 1) == 1);

				if (i < 8) {
					this.modules[8][this.moduleCount - i - 1] = mod;
				} else if (i < 9) {
					this.modules[8][15 - i - 1 + 1] = mod;
				} else {
					this.modules[8][15 - i - 1] = mod;
				}
			}

			// fixed module
			this.modules[this.moduleCount - 8][8] = (!test);

		},

		mapData : function(data, maskPattern) {

			var inc = -1;
			var row = this.moduleCount - 1;
			var bitIndex = 7;
			var byteIndex = 0;

			for (var col = this.moduleCount - 1; col > 0; col -= 2) {

				if (col == 6) col--;

				while (true) {

					for (var c = 0; c < 2; c++) {

						if (this.modules[row][col - c] == null) {

							var dark = false;

							if (byteIndex < data.length) {
								dark = ( ( (data[byteIndex] >>> bitIndex) & 1) == 1);
							}

							var mask = QRUtil.getMask(maskPattern, row, col - c);

							if (mask) {
								dark = !dark;
							}

							this.modules[row][col - c] = dark;
							bitIndex--;

							if (bitIndex == -1) {
								byteIndex++;
								bitIndex = 7;
							}
						}
					}

					row += inc;

					if (row < 0 || this.moduleCount <= row) {
						row -= inc;
						inc = -inc;
						break;
					}
				}
			}

		}

	};

	QRCode.PAD0 = 0xEC;
	QRCode.PAD1 = 0x11;

	QRCode.createData = function(typeNumber, errorCorrectLevel, dataList) {

		var rsBlocks = QRRSBlock.getRSBlocks(typeNumber, errorCorrectLevel);

		var buffer = new QRBitBuffer();

		for (var i = 0; i < dataList.length; i++) {
			var data = dataList[i];
			buffer.put(data.mode, 4);
			buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber) );
			data.write(buffer);
		}

		// calc num max data.
		var totalDataCount = 0;
		for (var i = 0; i < rsBlocks.length; i++) {
			totalDataCount += rsBlocks[i].dataCount;
		}

		if (buffer.getLengthInBits() > totalDataCount * 8) {
			throw new Error("code length overflow. ("
			+ buffer.getLengthInBits()
			+ ">"
			+  totalDataCount * 8
			+ ")");
		}

		// end code
		if (buffer.getLengthInBits() + 4 <= totalDataCount * 8) {
			buffer.put(0, 4);
		}

		// padding
		while (buffer.getLengthInBits() % 8 != 0) {
			buffer.putBit(false);
		}

		// padding
		while (true) {

			if (buffer.getLengthInBits() >= totalDataCount * 8) {
				break;
			}
			buffer.put(QRCode.PAD0, 8);

			if (buffer.getLengthInBits() >= totalDataCount * 8) {
				break;
			}
			buffer.put(QRCode.PAD1, 8);
		}

		return QRCode.createBytes(buffer, rsBlocks);
	}

	QRCode.createBytes = function(buffer, rsBlocks) {

		var offset = 0;

		var maxDcCount = 0;
		var maxEcCount = 0;

		var dcdata = new Array(rsBlocks.length);
		var ecdata = new Array(rsBlocks.length);

		for (var r = 0; r < rsBlocks.length; r++) {

			var dcCount = rsBlocks[r].dataCount;
			var ecCount = rsBlocks[r].totalCount - dcCount;

			maxDcCount = Math.max(maxDcCount, dcCount);
			maxEcCount = Math.max(maxEcCount, ecCount);

			dcdata[r] = new Array(dcCount);

			for (var i = 0; i < dcdata[r].length; i++) {
				dcdata[r][i] = 0xff & buffer.buffer[i + offset];
			}
			offset += dcCount;

			var rsPoly = QRUtil.getErrorCorrectPolynomial(ecCount);
			var rawPoly = new QRPolynomial(dcdata[r], rsPoly.getLength() - 1);

			var modPoly = rawPoly.mod(rsPoly);
			ecdata[r] = new Array(rsPoly.getLength() - 1);
			for (var i = 0; i < ecdata[r].length; i++) {
				var modIndex = i + modPoly.getLength() - ecdata[r].length;
				ecdata[r][i] = (modIndex >= 0)? modPoly.get(modIndex) : 0;
			}

		}

		var totalCodeCount = 0;
		for (var i = 0; i < rsBlocks.length; i++) {
			totalCodeCount += rsBlocks[i].totalCount;
		}

		var data = new Array(totalCodeCount);
		var index = 0;

		for (var i = 0; i < maxDcCount; i++) {
			for (var r = 0; r < rsBlocks.length; r++) {
				if (i < dcdata[r].length) {
					data[index++] = dcdata[r][i];
				}
			}
		}

		for (var i = 0; i < maxEcCount; i++) {
			for (var r = 0; r < rsBlocks.length; r++) {
				if (i < ecdata[r].length) {
					data[index++] = ecdata[r][i];
				}
			}
		}

		return data;

	}

//---------------------------------------------------------------------
// QRMode
//---------------------------------------------------------------------

	var QRMode = {
		MODE_NUMBER :		1 << 0,
		MODE_ALPHA_NUM : 	1 << 1,
		MODE_8BIT_BYTE : 	1 << 2,
		MODE_KANJI :		1 << 3
	};

//---------------------------------------------------------------------
// QRErrorCorrectLevel
//---------------------------------------------------------------------

	var QRErrorCorrectLevel = {
		L : 1,
		M : 0,
		Q : 3,
		H : 2
	};

//---------------------------------------------------------------------
// QRMaskPattern
//---------------------------------------------------------------------

	var QRMaskPattern = {
		PATTERN000 : 0,
		PATTERN001 : 1,
		PATTERN010 : 2,
		PATTERN011 : 3,
		PATTERN100 : 4,
		PATTERN101 : 5,
		PATTERN110 : 6,
		PATTERN111 : 7
	};

//---------------------------------------------------------------------
// QRUtil
//---------------------------------------------------------------------

	var QRUtil = {

		PATTERN_POSITION_TABLE : [
			[],
			[6, 18],
			[6, 22],
			[6, 26],
			[6, 30],
			[6, 34],
			[6, 22, 38],
			[6, 24, 42],
			[6, 26, 46],
			[6, 28, 50],
			[6, 30, 54],
			[6, 32, 58],
			[6, 34, 62],
			[6, 26, 46, 66],
			[6, 26, 48, 70],
			[6, 26, 50, 74],
			[6, 30, 54, 78],
			[6, 30, 56, 82],
			[6, 30, 58, 86],
			[6, 34, 62, 90],
			[6, 28, 50, 72, 94],
			[6, 26, 50, 74, 98],
			[6, 30, 54, 78, 102],
			[6, 28, 54, 80, 106],
			[6, 32, 58, 84, 110],
			[6, 30, 58, 86, 114],
			[6, 34, 62, 90, 118],
			[6, 26, 50, 74, 98, 122],
			[6, 30, 54, 78, 102, 126],
			[6, 26, 52, 78, 104, 130],
			[6, 30, 56, 82, 108, 134],
			[6, 34, 60, 86, 112, 138],
			[6, 30, 58, 86, 114, 142],
			[6, 34, 62, 90, 118, 146],
			[6, 30, 54, 78, 102, 126, 150],
			[6, 24, 50, 76, 102, 128, 154],
			[6, 28, 54, 80, 106, 132, 158],
			[6, 32, 58, 84, 110, 136, 162],
			[6, 26, 54, 82, 110, 138, 166],
			[6, 30, 58, 86, 114, 142, 170]
		],

		G15 : (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0),
		G18 : (1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0),
		G15_MASK : (1 << 14) | (1 << 12) | (1 << 10)	| (1 << 4) | (1 << 1),

		getBCHTypeInfo : function(data) {
			var d = data << 10;
			while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15) >= 0) {
				d ^= (QRUtil.G15 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15) ) );
			}
			return ( (data << 10) | d) ^ QRUtil.G15_MASK;
		},

		getBCHTypeNumber : function(data) {
			var d = data << 12;
			while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18) >= 0) {
				d ^= (QRUtil.G18 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18) ) );
			}
			return (data << 12) | d;
		},

		getBCHDigit : function(data) {

			var digit = 0;

			while (data != 0) {
				digit++;
				data >>>= 1;
			}

			return digit;
		},

		getPatternPosition : function(typeNumber) {
			return QRUtil.PATTERN_POSITION_TABLE[typeNumber - 1];
		},

		getMask : function(maskPattern, i, j) {

			switch (maskPattern) {

				case QRMaskPattern.PATTERN000 : return (i + j) % 2 == 0;
				case QRMaskPattern.PATTERN001 : return i % 2 == 0;
				case QRMaskPattern.PATTERN010 : return j % 3 == 0;
				case QRMaskPattern.PATTERN011 : return (i + j) % 3 == 0;
				case QRMaskPattern.PATTERN100 : return (Math.floor(i / 2) + Math.floor(j / 3) ) % 2 == 0;
				case QRMaskPattern.PATTERN101 : return (i * j) % 2 + (i * j) % 3 == 0;
				case QRMaskPattern.PATTERN110 : return ( (i * j) % 2 + (i * j) % 3) % 2 == 0;
				case QRMaskPattern.PATTERN111 : return ( (i * j) % 3 + (i + j) % 2) % 2 == 0;

				default :
					throw new Error("bad maskPattern:" + maskPattern);
			}
		},

		getErrorCorrectPolynomial : function(errorCorrectLength) {

			var a = new QRPolynomial([1], 0);

			for (var i = 0; i < errorCorrectLength; i++) {
				a = a.multiply(new QRPolynomial([1, QRMath.gexp(i)], 0) );
			}

			return a;
		},

		getLengthInBits : function(mode, type) {

			if (1 <= type && type < 10) {

				// 1 - 9

				switch(mode) {
					case QRMode.MODE_NUMBER 	: return 10;
					case QRMode.MODE_ALPHA_NUM 	: return 9;
					case QRMode.MODE_8BIT_BYTE	: return 8;
					case QRMode.MODE_KANJI  	: return 8;
					default :
						throw new Error("mode:" + mode);
				}

			} else if (type < 27) {

				// 10 - 26

				switch(mode) {
					case QRMode.MODE_NUMBER 	: return 12;
					case QRMode.MODE_ALPHA_NUM 	: return 11;
					case QRMode.MODE_8BIT_BYTE	: return 16;
					case QRMode.MODE_KANJI  	: return 10;
					default :
						throw new Error("mode:" + mode);
				}

			} else if (type < 41) {

				// 27 - 40

				switch(mode) {
					case QRMode.MODE_NUMBER 	: return 14;
					case QRMode.MODE_ALPHA_NUM	: return 13;
					case QRMode.MODE_8BIT_BYTE	: return 16;
					case QRMode.MODE_KANJI  	: return 12;
					default :
						throw new Error("mode:" + mode);
				}

			} else {
				throw new Error("type:" + type);
			}
		},

		getLostPoint : function(qrCode) {

			var moduleCount = qrCode.getModuleCount();

			var lostPoint = 0;

			// LEVEL1

			for (var row = 0; row < moduleCount; row++) {

				for (var col = 0; col < moduleCount; col++) {

					var sameCount = 0;
					var dark = qrCode.isDark(row, col);

					for (var r = -1; r <= 1; r++) {

						if (row + r < 0 || moduleCount <= row + r) {
							continue;
						}

						for (var c = -1; c <= 1; c++) {

							if (col + c < 0 || moduleCount <= col + c) {
								continue;
							}

							if (r == 0 && c == 0) {
								continue;
							}

							if (dark == qrCode.isDark(row + r, col + c) ) {
								sameCount++;
							}
						}
					}

					if (sameCount > 5) {
						lostPoint += (3 + sameCount - 5);
					}
				}
			}

			// LEVEL2

			for (var row = 0; row < moduleCount - 1; row++) {
				for (var col = 0; col < moduleCount - 1; col++) {
					var count = 0;
					if (qrCode.isDark(row,     col    ) ) count++;
					if (qrCode.isDark(row + 1, col    ) ) count++;
					if (qrCode.isDark(row,     col + 1) ) count++;
					if (qrCode.isDark(row + 1, col + 1) ) count++;
					if (count == 0 || count == 4) {
						lostPoint += 3;
					}
				}
			}

			// LEVEL3

			for (var row = 0; row < moduleCount; row++) {
				for (var col = 0; col < moduleCount - 6; col++) {
					if (qrCode.isDark(row, col)
						&& !qrCode.isDark(row, col + 1)
						&&  qrCode.isDark(row, col + 2)
						&&  qrCode.isDark(row, col + 3)
						&&  qrCode.isDark(row, col + 4)
						&& !qrCode.isDark(row, col + 5)
						&&  qrCode.isDark(row, col + 6) ) {
						lostPoint += 40;
					}
				}
			}

			for (var col = 0; col < moduleCount; col++) {
				for (var row = 0; row < moduleCount - 6; row++) {
					if (qrCode.isDark(row, col)
						&& !qrCode.isDark(row + 1, col)
						&&  qrCode.isDark(row + 2, col)
						&&  qrCode.isDark(row + 3, col)
						&&  qrCode.isDark(row + 4, col)
						&& !qrCode.isDark(row + 5, col)
						&&  qrCode.isDark(row + 6, col) ) {
						lostPoint += 40;
					}
				}
			}

			// LEVEL4

			var darkCount = 0;

			for (var col = 0; col < moduleCount; col++) {
				for (var row = 0; row < moduleCount; row++) {
					if (qrCode.isDark(row, col) ) {
						darkCount++;
					}
				}
			}

			var ratio = Math.abs(100 * darkCount / moduleCount / moduleCount - 50) / 5;
			lostPoint += ratio * 10;

			return lostPoint;
		}

	};


//---------------------------------------------------------------------
// QRMath
//---------------------------------------------------------------------

	var QRMath = {

		glog : function(n) {

			if (n < 1) {
				throw new Error("glog(" + n + ")");
			}

			return QRMath.LOG_TABLE[n];
		},

		gexp : function(n) {

			while (n < 0) {
				n += 255;
			}

			while (n >= 256) {
				n -= 255;
			}

			return QRMath.EXP_TABLE[n];
		},

		EXP_TABLE : new Array(256),

		LOG_TABLE : new Array(256)

	};

	for (var i = 0; i < 8; i++) {
		QRMath.EXP_TABLE[i] = 1 << i;
	}
	for (var i = 8; i < 256; i++) {
		QRMath.EXP_TABLE[i] = QRMath.EXP_TABLE[i - 4]
		^ QRMath.EXP_TABLE[i - 5]
		^ QRMath.EXP_TABLE[i - 6]
		^ QRMath.EXP_TABLE[i - 8];
	}
	for (var i = 0; i < 255; i++) {
		QRMath.LOG_TABLE[QRMath.EXP_TABLE[i] ] = i;
	}

//---------------------------------------------------------------------
// QRPolynomial
//---------------------------------------------------------------------

	function QRPolynomial(num, shift) {

		if (num.length == undefined) {
			throw new Error(num.length + "/" + shift);
		}

		var offset = 0;

		while (offset < num.length && num[offset] == 0) {
			offset++;
		}

		this.num = new Array(num.length - offset + shift);
		for (var i = 0; i < num.length - offset; i++) {
			this.num[i] = num[i + offset];
		}
	}

	QRPolynomial.prototype = {

		get : function(index) {
			return this.num[index];
		},

		getLength : function() {
			return this.num.length;
		},

		multiply : function(e) {

			var num = new Array(this.getLength() + e.getLength() - 1);

			for (var i = 0; i < this.getLength(); i++) {
				for (var j = 0; j < e.getLength(); j++) {
					num[i + j] ^= QRMath.gexp(QRMath.glog(this.get(i) ) + QRMath.glog(e.get(j) ) );
				}
			}

			return new QRPolynomial(num, 0);
		},

		mod : function(e) {

			if (this.getLength() - e.getLength() < 0) {
				return this;
			}

			var ratio = QRMath.glog(this.get(0) ) - QRMath.glog(e.get(0) );

			var num = new Array(this.getLength() );

			for (var i = 0; i < this.getLength(); i++) {
				num[i] = this.get(i);
			}

			for (var i = 0; i < e.getLength(); i++) {
				num[i] ^= QRMath.gexp(QRMath.glog(e.get(i) ) + ratio);
			}

			// recursive call
			return new QRPolynomial(num, 0).mod(e);
		}
	};

//---------------------------------------------------------------------
// QRRSBlock
//---------------------------------------------------------------------

	function QRRSBlock(totalCount, dataCount) {
		this.totalCount = totalCount;
		this.dataCount  = dataCount;
	}

	QRRSBlock.RS_BLOCK_TABLE = [

		// L
		// M
		// Q
		// H

		// 1
		[1, 26, 19],
		[1, 26, 16],
		[1, 26, 13],
		[1, 26, 9],

		// 2
		[1, 44, 34],
		[1, 44, 28],
		[1, 44, 22],
		[1, 44, 16],

		// 3
		[1, 70, 55],
		[1, 70, 44],
		[2, 35, 17],
		[2, 35, 13],

		// 4
		[1, 100, 80],
		[2, 50, 32],
		[2, 50, 24],
		[4, 25, 9],

		// 5
		[1, 134, 108],
		[2, 67, 43],
		[2, 33, 15, 2, 34, 16],
		[2, 33, 11, 2, 34, 12],

		// 6
		[2, 86, 68],
		[4, 43, 27],
		[4, 43, 19],
		[4, 43, 15],

		// 7
		[2, 98, 78],
		[4, 49, 31],
		[2, 32, 14, 4, 33, 15],
		[4, 39, 13, 1, 40, 14],

		// 8
		[2, 121, 97],
		[2, 60, 38, 2, 61, 39],
		[4, 40, 18, 2, 41, 19],
		[4, 40, 14, 2, 41, 15],

		// 9
		[2, 146, 116],
		[3, 58, 36, 2, 59, 37],
		[4, 36, 16, 4, 37, 17],
		[4, 36, 12, 4, 37, 13],

		// 10
		[2, 86, 68, 2, 87, 69],
		[4, 69, 43, 1, 70, 44],
		[6, 43, 19, 2, 44, 20],
		[6, 43, 15, 2, 44, 16],

		// 11
		[4, 101, 81],
		[1, 80, 50, 4, 81, 51],
		[4, 50, 22, 4, 51, 23],
		[3, 36, 12, 8, 37, 13],

		// 12
		[2, 116, 92, 2, 117, 93],
		[6, 58, 36, 2, 59, 37],
		[4, 46, 20, 6, 47, 21],
		[7, 42, 14, 4, 43, 15],

		// 13
		[4, 133, 107],
		[8, 59, 37, 1, 60, 38],
		[8, 44, 20, 4, 45, 21],
		[12, 33, 11, 4, 34, 12],

		// 14
		[3, 145, 115, 1, 146, 116],
		[4, 64, 40, 5, 65, 41],
		[11, 36, 16, 5, 37, 17],
		[11, 36, 12, 5, 37, 13],

		// 15
		[5, 109, 87, 1, 110, 88],
		[5, 65, 41, 5, 66, 42],
		[5, 54, 24, 7, 55, 25],
		[11, 36, 12],

		// 16
		[5, 122, 98, 1, 123, 99],
		[7, 73, 45, 3, 74, 46],
		[15, 43, 19, 2, 44, 20],
		[3, 45, 15, 13, 46, 16],

		// 17
		[1, 135, 107, 5, 136, 108],
		[10, 74, 46, 1, 75, 47],
		[1, 50, 22, 15, 51, 23],
		[2, 42, 14, 17, 43, 15],

		// 18
		[5, 150, 120, 1, 151, 121],
		[9, 69, 43, 4, 70, 44],
		[17, 50, 22, 1, 51, 23],
		[2, 42, 14, 19, 43, 15],

		// 19
		[3, 141, 113, 4, 142, 114],
		[3, 70, 44, 11, 71, 45],
		[17, 47, 21, 4, 48, 22],
		[9, 39, 13, 16, 40, 14],

		// 20
		[3, 135, 107, 5, 136, 108],
		[3, 67, 41, 13, 68, 42],
		[15, 54, 24, 5, 55, 25],
		[15, 43, 15, 10, 44, 16],

		// 21
		[4, 144, 116, 4, 145, 117],
		[17, 68, 42],
		[17, 50, 22, 6, 51, 23],
		[19, 46, 16, 6, 47, 17],

		// 22
		[2, 139, 111, 7, 140, 112],
		[17, 74, 46],
		[7, 54, 24, 16, 55, 25],
		[34, 37, 13],

		// 23
		[4, 151, 121, 5, 152, 122],
		[4, 75, 47, 14, 76, 48],
		[11, 54, 24, 14, 55, 25],
		[16, 45, 15, 14, 46, 16],

		// 24
		[6, 147, 117, 4, 148, 118],
		[6, 73, 45, 14, 74, 46],
		[11, 54, 24, 16, 55, 25],
		[30, 46, 16, 2, 47, 17],

		// 25
		[8, 132, 106, 4, 133, 107],
		[8, 75, 47, 13, 76, 48],
		[7, 54, 24, 22, 55, 25],
		[22, 45, 15, 13, 46, 16],

		// 26
		[10, 142, 114, 2, 143, 115],
		[19, 74, 46, 4, 75, 47],
		[28, 50, 22, 6, 51, 23],
		[33, 46, 16, 4, 47, 17],

		// 27
		[8, 152, 122, 4, 153, 123],
		[22, 73, 45, 3, 74, 46],
		[8, 53, 23, 26, 54, 24],
		[12, 45, 15, 28, 46, 16],

		// 28
		[3, 147, 117, 10, 148, 118],
		[3, 73, 45, 23, 74, 46],
		[4, 54, 24, 31, 55, 25],
		[11, 45, 15, 31, 46, 16],

		// 29
		[7, 146, 116, 7, 147, 117],
		[21, 73, 45, 7, 74, 46],
		[1, 53, 23, 37, 54, 24],
		[19, 45, 15, 26, 46, 16],

		// 30
		[5, 145, 115, 10, 146, 116],
		[19, 75, 47, 10, 76, 48],
		[15, 54, 24, 25, 55, 25],
		[23, 45, 15, 25, 46, 16],

		// 31
		[13, 145, 115, 3, 146, 116],
		[2, 74, 46, 29, 75, 47],
		[42, 54, 24, 1, 55, 25],
		[23, 45, 15, 28, 46, 16],

		// 32
		[17, 145, 115],
		[10, 74, 46, 23, 75, 47],
		[10, 54, 24, 35, 55, 25],
		[19, 45, 15, 35, 46, 16],

		// 33
		[17, 145, 115, 1, 146, 116],
		[14, 74, 46, 21, 75, 47],
		[29, 54, 24, 19, 55, 25],
		[11, 45, 15, 46, 46, 16],

		// 34
		[13, 145, 115, 6, 146, 116],
		[14, 74, 46, 23, 75, 47],
		[44, 54, 24, 7, 55, 25],
		[59, 46, 16, 1, 47, 17],

		// 35
		[12, 151, 121, 7, 152, 122],
		[12, 75, 47, 26, 76, 48],
		[39, 54, 24, 14, 55, 25],
		[22, 45, 15, 41, 46, 16],

		// 36
		[6, 151, 121, 14, 152, 122],
		[6, 75, 47, 34, 76, 48],
		[46, 54, 24, 10, 55, 25],
		[2, 45, 15, 64, 46, 16],

		// 37
		[17, 152, 122, 4, 153, 123],
		[29, 74, 46, 14, 75, 47],
		[49, 54, 24, 10, 55, 25],
		[24, 45, 15, 46, 46, 16],

		// 38
		[4, 152, 122, 18, 153, 123],
		[13, 74, 46, 32, 75, 47],
		[48, 54, 24, 14, 55, 25],
		[42, 45, 15, 32, 46, 16],

		// 39
		[20, 147, 117, 4, 148, 118],
		[40, 75, 47, 7, 76, 48],
		[43, 54, 24, 22, 55, 25],
		[10, 45, 15, 67, 46, 16],

		// 40
		[19, 148, 118, 6, 149, 119],
		[18, 75, 47, 31, 76, 48],
		[34, 54, 24, 34, 55, 25],
		[20, 45, 15, 61, 46, 16]
	];

	QRRSBlock.getRSBlocks = function(typeNumber, errorCorrectLevel) {

		var rsBlock = QRRSBlock.getRsBlockTable(typeNumber, errorCorrectLevel);

		if (rsBlock == undefined) {
			throw new Error("bad rs block @ typeNumber:" + typeNumber + "/errorCorrectLevel:" + errorCorrectLevel);
		}

		var length = rsBlock.length / 3;

		var list = new Array();

		for (var i = 0; i < length; i++) {

			var count = rsBlock[i * 3 + 0];
			var totalCount = rsBlock[i * 3 + 1];
			var dataCount  = rsBlock[i * 3 + 2];

			for (var j = 0; j < count; j++) {
				list.push(new QRRSBlock(totalCount, dataCount) );
			}
		}

		return list;
	}

	QRRSBlock.getRsBlockTable = function(typeNumber, errorCorrectLevel) {

		switch(errorCorrectLevel) {
			case QRErrorCorrectLevel.L :
				return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 0];
			case QRErrorCorrectLevel.M :
				return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 1];
			case QRErrorCorrectLevel.Q :
				return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 2];
			case QRErrorCorrectLevel.H :
				return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 3];
			default :
				return undefined;
		}
	}

//---------------------------------------------------------------------
// QRBitBuffer
//---------------------------------------------------------------------

	function QRBitBuffer() {
		this.buffer = new Array();
		this.length = 0;
	}

	QRBitBuffer.prototype = {

		get : function(index) {
			var bufIndex = Math.floor(index / 8);
			return ( (this.buffer[bufIndex] >>> (7 - index % 8) ) & 1) == 1;
		},

		put : function(num, length) {
			for (var i = 0; i < length; i++) {
				this.putBit( ( (num >>> (length - i - 1) ) & 1) == 1);
			}
		},

		getLengthInBits : function() {
			return this.length;
		},

		putBit : function(bit) {

			var bufIndex = Math.floor(this.length / 8);
			if (this.buffer.length <= bufIndex) {
				this.buffer.push(0);
			}

			if (bit) {
				this.buffer[bufIndex] |= (0x80 >>> (this.length % 8) );
			}

			this.length++;
		}
	};

	/**
	 * @description Kael-Qrcode 基于canvas灵活可配置的二维码生成库
	 * @version 1.0
	 * @author litten
	 * @dependencies 依赖于同文件夹下qrcode.js；已将两份资源打包成build/kaelQrcode.min.js
	 * @lastUpdate 2014-08-10 0:11
	 */

	var KaelQrcode = function(){

		var basicConfig = {};
		var config = {};
		var qrcode, canvas, ctx;

		var Tool = {
			//RGB颜色转换为16进制
			colorHex: function(color){
				var reg = /^#([0-9a-fA-f]{3}|[0-9a-fA-f]{6})$/;
				var that = color;
				if(/^(rgb|RGB)/.test(that)){
					var aColor = that.replace(/(?:\(|\)|rgb|RGB)*/g,"").split(",");
					var strHex = "#";
					for(var i=0; i<aColor.length; i++){
						var hex = Number(aColor[i]).toString(16);
						if(hex === "0"){
							hex += hex;
						}
						strHex += hex;
					}
					if(strHex.length !== 7){
						strHex = that;
					}
					return strHex;
				}else if(reg.test(that)){
					var aNum = that.replace(/#/,"").split("");
					if(aNum.length === 6){
						return that;
					}else if(aNum.length === 3){
						var numHex = "#";
						for(var i=0; i<aNum.length; i+=1){
							numHex += (aNum[i]+aNum[i]);
						}
						return numHex;
					}
				}else{
					return that;
				}
			},
			//16进制颜色转为RGB格式
			colorRgb: function(color){
				var reg = /^#([0-9a-fA-f]{3}|[0-9a-fA-f]{6})$/;
				var sColor = color.toLowerCase();
				if(sColor && reg.test(sColor)){
					if(sColor.length === 4){
						var sColorNew = "#";
						for(var i=1; i<4; i+=1){
							sColorNew += sColor.slice(i,i+1).concat(sColor.slice(i,i+1));
						}
						sColor = sColorNew;
					}
					//处理六位的颜色值
					var sColorChange = [];
					for(var i=1; i<7; i+=2){
						sColorChange.push(parseInt("0x"+sColor.slice(i,i+2)));
					}
					return "RGB(" + sColorChange.join(",") + ")";
				}else{
					return sColor;
				}
			},
			//颜色增量变化，支持rgb，16进制和linear
			changeRGB : function(curcolor, num){
				function addRGB(val){
					var valFormat = (val.match(/\([^\)]+\)/g))[0];
					valFormat = valFormat.substr(1, valFormat.length-2);
					var arr = valFormat.split(",");
					for(var i=0,len=arr.length;i<len;i++){
						arr[i] = parseInt(arr[i]) + num;
						if(arr[i] < 0){
							arr[i] = 0;
						}
					}
					return "rgb("+arr.join(",")+")";
				}
				if(typeof(curcolor) == "object"){
					var linear  = ctx.createLinearGradient(0,0, 0, config.width);
					for(var em in curcolor){
						var val = curcolor[em];
						val = addRGB(val);
						linear.addColorStop(em, val);
					}
					return linear;
				}else if(typeof(curcolor) == "string"){
					if(curcolor.indexOf("#") < 0){
						return addRGB(curcolor);
					}else{
						return addRGB(Tool.colorRgb(curcolor));
					}
				}
			}
		}

		//画布初始化
		var canvasInit = function(){
			canvas = document.createElement('canvas');
			canvas.width = config.width;
			canvas.height = config.height;
			ctx	= canvas.getContext('2d');
		}
		//二维码数据初始化
		var qrcodeInit = function(){
			qrcode	= new QRCode(config.typeNumber, config.correctLevel);
			qrcode.addData(config.text);
			qrcode.make();
		}
		//检测色块边缘
		var edgeTest = function(row, col){
			var len = qrcode.getModuleCount();
			var obj = {
				"l": false,
				"r": false,
				"t": false,
				"b": false,
				"row": row,
				"col": col
			};

			if(col != 0 && qrcode.isDark(row, col-1)){
				obj["l"] = true;
			}
			if(col != len-1 && qrcode.isDark(row, col+1)){
				obj["r"] = true;
			}
			if(row != 0 && qrcode.isDark(row-1, col)){
				obj["t"] = true;
			}
			if(row != len-1 && qrcode.isDark(row+1, col)){
				obj["b"] = true;
			}
			if(row == 8 && col == 6){
				//console.log(obj);
			}
			return obj;
		}


		//画图片
		var drawImg = function(){
			var img = new Image();
			img.src = config.img.src;
			var imgSize = config.width/5;
			var imgPos = config.width/10*4;
			var imgPosFix = config.width/120;

			//图片border
			ctx.strokeStyle = config.img.border || '#fff';
			ctx.lineWidth   = config.width/40;
			ctx.globalAlpha   = 1;
			ctx.lineCap = "round";
			ctx.lineJoin = "round";

			ctx.beginPath();
			ctx.moveTo(imgPos-imgPosFix, imgPos-imgPosFix);
			ctx.lineTo(imgPos+imgSize+imgPosFix, imgPos-imgPosFix);
			ctx.lineTo(imgPos+imgSize+imgPosFix, imgPos+imgSize+imgPosFix);
			ctx.lineTo(imgPos-imgPosFix, imgPos+imgSize+imgPosFix);
			ctx.lineTo(imgPos-imgPosFix, imgPos-imgPosFix);
			ctx.stroke();
			ctx.closePath();

			img.onload = function(){
				ctx.drawImage(img,imgPos,imgPos,imgSize,imgSize);
				ctx.beginPath();
			}
		}
		//画单点
		var drawPoint = function(edgeResult, isShadow){
			var shadowColor = Tool.changeRGB(basicConfig.color, -20);
			var pointShadowColor = Tool.changeRGB(basicConfig.pointColor, -20);

			if((edgeResult["l"] || edgeResult["r"] || edgeResult["t"] || edgeResult["b"])){
				if(isShadow){
					ctx.fillStyle = shadowColor;
				}else{
					ctx.strokeStyle = config.color;
					ctx.fillStyle = config.color;
				}

			}else{
				if(isShadow){
					ctx.fillStyle = pointShadowColor;
				}else{
					ctx.strokeStyle = config.pointColor;
					ctx.fillStyle = config.pointColor;
				}

			}
		}
		//画背景
		var drawBg = function(){
			ctx.fillStyle = config.background;
			ctx.fillRect(0, 0, config.width, config.height);
		}

		//画二维码
		var drawCode = function(type, opt, isShadow){
			var row = opt.row;
			var col = opt.col;
			var tileW = opt.tileW;
			var tileH = opt.tileH;
			var w = opt.w;
			var h = opt.h;

			var shadowColor = Tool.changeRGB(basicConfig.color, -20);
			var pointShadowColor = Tool.changeRGB(basicConfig.pointColor, -20);

			if(type == "round"){
				//圆角
				var isDark = qrcode.isDark(row, col);
				if(isDark){

					var edgeResult = edgeTest(row, col);

					var imgSize = config.width/5;
					var imgPos = config.width/10*4;
					//图片border
					if(isShadow){
						ctx.fillStyle = shadowColor;
						ctx.strokeStyle = shadowColor;
					}else{
						///////////////todo：确认有没有视觉偏差
						var w = (col+1)*tileW - col*tileW;
						var h = (row+1)*tileW - row*tileW;

						//单点设定颜色
						if(config.pointColor){
							drawPoint(edgeResult, isShadow);
						}else{
							ctx.fillStyle = config.color;
							ctx.strokeStyle = config.color;
						}
					}
					ctx.lineWidth   = 2;
					ctx.globalAlpha   = 1;
					ctx.lineCap = "round";
					ctx.lineJoin = "round";
					ctx.beginPath();



					var posX = Math.round(col*tileW);
					var posY = Math.round(row*tileH);
					var r = w/2;


					//console.log(edgeResult);

					if(!edgeResult["l"] && !edgeResult["t"]){
						//左上角圆角
						ctx.moveTo(posX+r, posY);
					}else{
						ctx.moveTo(posX, posY);
					}

					if(!edgeResult["r"] && !edgeResult["t"]){
						//右上角圆角
						ctx.arcTo(posX+w, posY, posX+w, posY+h, r);
					}else{
						ctx.lineTo(posX + w, posY);
					}

					if(!edgeResult["r"] && !edgeResult["b"]){
						//右下角圆角
						ctx.arcTo(posX+w, posY+h, posX, posY+h, r);
					}else{
						ctx.lineTo(posX + w, posY + h);
					}

					if(!edgeResult["l"] && !edgeResult["b"]){
						//左下角圆角
						ctx.arcTo(posX, posY+h, posX, posY, r);
					}else{
						ctx.lineTo(posX, posY + h);
					}

					if(!edgeResult["l"] && !edgeResult["t"]){
						//左上角圆角
						ctx.arcTo(posX, posY, posX+w, posY, r);
					}else{
						ctx.lineTo(posX, posY);
					}



					ctx.stroke();

					ctx.fill();
					ctx.closePath();
				}
			}else{
				//基本类型
				//单点设定颜色
				var isDark = qrcode.isDark(row, col);
				if(isDark){


					if(config.pointColor){
						drawPoint(edgeTest(row, col), isShadow);
					}else{
						if(isShadow){
							ctx.fillStyle = shadowColor;
						}else{
							ctx.fillStyle = config.color;
						}
					}
					ctx.fillRect(Math.round(col*tileW),Math.round(row*tileH), w, h);
				}
			}

		}

		//生成二维码
		var createCanvas = function(){

			var tileW	= config.width  / qrcode.getModuleCount();
			var tileH	= config.height / qrcode.getModuleCount();

			drawBg();

			//绘制阴影
			if(config.shadow){
				for( var row = 0; row < qrcode.getModuleCount(); row++ ){
					for( var col = 0; col < qrcode.getModuleCount(); col++ ){
						var w = (Math.ceil((col+1)*tileW) - Math.floor(col*tileW));
						var h = (Math.ceil((row+1)*tileW) - Math.floor(row*tileW));
						var shadowW = config.width/150;
						drawCode(config.type, {
							row: row,
							col: col,
							tileW: tileW,
							tileH: tileH,
							w: w+shadowW,
							h: h+shadowW
						}, true);
					}
				}
			}
			//基本
			for( var row = 0; row < qrcode.getModuleCount(); row++ ){
				for( var col = 0; col < qrcode.getModuleCount(); col++ ){
					var w = (Math.ceil((col+1)*tileW) - Math.floor(col*tileW));
					var h = (Math.ceil((row+1)*tileW) - Math.floor(row*tileW));
					drawCode(config.type, {
						row: row,
						col: col,
						tileW: tileW,
						tileH: tileH,
						w: w,
						h: h
					});

				}
			}

			//绘制图片
			if(config.img){
				drawImg();
			}

			return canvas;

		}

		var utf16to8 = function(str) {
			var out, i, len, c;
			out = "";
			len = str.length;
			for(i = 0; i < len; i++) {
				c = str.charCodeAt(i);
				if ((c >= 0x0001) && (c <= 0x007F)) {
					out += str.charAt(i);
				} else if (c > 0x07FF) {
					out += String.fromCharCode(0xE0 | ((c >> 12) & 0x0F));
					out += String.fromCharCode(0x80 | ((c >>  6) & 0x3F));
					out += String.fromCharCode(0x80 | ((c >>  0) & 0x3F));
				} else {
					out += String.fromCharCode(0xC0 | ((c >>  6) & 0x1F));
					out += String.fromCharCode(0x80 | ((c >>  0) & 0x3F));
				}
			}
			return out;
		}

		var init = function(options){
			options.text = utf16to8(options.text); //解决中文乱码问题

			basicConfig = options;

			if( typeof options === 'string' ){
				config = options = { text: options };
			}else{
				config.text = options.text || "http://jeasytp.com";
			}

			config.width = options.size || 150;
			config.height = options.size || 150;
			config.shadow = options.shadow || false;

			canvasInit();

			config.typeNumber = options.typeNumber || -1;
			config.correctLevel = QRErrorCorrectLevel.H;
			config.pointColor = options.pointColor || null;

			config.type = options.type || "base";


			//图片
			if(typeof(options.img) == "string"){
				config.img = {
					src: options.img
				}
			}else{
				config.img = options.img
			}
			//背景
			if(options.background){
				var type = typeof(options.background);
				if(type == "string"){
					config.background = options.background;
				}else if(type == "object"){
					var linear  = ctx.createLinearGradient(0,0, 0, config.width);
					for(var em in options.background){
						linear.addColorStop(parseInt(em), options.background[em]);
					}
					config.background = linear;
				}else{
					config.background = "#fff";
				}
			}else{
				config.background = "#fff";
			}
			//前景色
			if(options.color){
				var type = typeof(options.color);
				if(type == "string"){
					config.color = options.color;
				}else if(type == "object"){
					var linear  = ctx.createLinearGradient(0,0, 0, config.width);
					for(var em in options.color){
						linear.addColorStop(em, options.color[em]);
					}
					config.color = linear;
				}else{
					config.color = "#000";
				}
			}else{
				config.color = "#000";
			}


			qrcodeInit();
			var canvas = createCanvas();
			return canvas.toDataURL("image/png");
		}

		return {
			init: init,
			config: config
		}
	}

	return new KaelQrcode();
});