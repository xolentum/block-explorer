// Copyright (c) 2018, The NERVA Project
// Copyright (c) 2014-2017, MyMonero.com

// Based on cn_util.js
// Original Author: Lucas Jones
// Modified to remove jQuery dep and support modular inclusion of deps by Paul Shapiro (2016)
// Modified by luigi1111 2017

var HASH_STATE_BYTES = 200;
var HASH_SIZE = 32;
var ADDRESS_CHECKSUM_SIZE = 4;
var INTEGRATED_ID_SIZE = 8;
var ENCRYPTED_PAYMENT_ID_TAIL = 141;
var CRYPTONOTE_PUBLIC_ADDRESS_BASE58_PREFIX = 0x3800;
var CRYPTONOTE_PUBLIC_INTEGRATED_ADDRESS_BASE58_PREFIX = 0x7081;
var CRYPTONOTE_PUBLIC_SUBADDRESS_BASE58_PREFIX = 0x1080;

var H = "8b655970153799af2aeadc9ff1add0ea6c7251d54154cfa92c173a0dd39c1f94"; //base H for amounts
var l = JSBigInt("7237005577332262213973186563042994240857116359379907606001950938285454250989"); //curve order (not RCT specific)
var I = "0100000000000000000000000000000000000000000000000000000000000000"; //identity element
var Z = "0000000000000000000000000000000000000000000000000000000000000000"; //zero scalar

var STRUCT_SIZES = {
    GE_P3: 160,
    GE_P2: 120,
    GE_P1P1: 160,
    GE_CACHED: 160,
    EC_SCALAR: 32,
    EC_POINT: 32,
    KEY_IMAGE: 32,
    GE_DSMP: 160 * 8, // ge_cached * 8
    SIGNATURE: 64 // ec_scalar * 2
};

function encodeVarint(i)
{
    i = new JSBigInt(i);
    var out = '';
    // While i >= b10000000
    while (i.compare(0x80) >= 0) {
        // out.append i & b01111111 | b10000000
        out += ("0" + ((i.lowVal() & 0x7f) | 0x80).toString(16)).slice(-2);
        i = i.divide(new JSBigInt(2).pow(7));
    }
    out += ("0" + i.toJSValue().toString(16)).slice(-2);
    return out;
}

function validHex(hex)
{
    var exp = new RegExp("[0-9a-fA-F]{" + hex.length + "}");
    return exp.test(hex);
}

function hexToBin(hex)
{
    if (hex.length % 2 !== 0) throw "Hex string has invalid length!";
    var res = new Uint8Array(hex.length / 2);
    for (var i = 0; i < hex.length / 2; ++i)
        res[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
    
    return res;
}

function binToHex(bin)
{
    var out = [];
    for (var i = 0; i < bin.length; ++i)
        out.push(("0" + bin[i].toString(16)).slice(-2));

    return out.join("");
}

function cnFastHash(input, inlen)
{
    if (input.length % 2 !== 0 || !validHex(input))
        throw "Input invalid";

    var bin = hexToBin(input);
    var result = keccak_256(bin)
    return result;
}

const address_type = {
    unknown: "Unknown",
    standard: "Standard",
    integrated: "Integrated",
    subaddress: "Subaddress"
}

//==============================================================================

function geScalarMult(pub, sec)
{
    if (pub.length !== 64 || sec.length !== 64) throw "geScalarMult: Invalid pub or sec input lengths";

    return binToHex(nacl.ll.ge_scalarmult(hexToBin(pub), hexToBin(sec)));
}

function swapEndian(hex)
{
    if (hex.length % 2 !== 0) throw "length must be a multiple of 2!";
    var data = "";
    for (var i=1; i <= hex.length / 2; i++)
        data += hex.substr(0 - 2 * i, 2);
    
    return data;
}

function d2h(integer)
{
    if (typeof integer !== "string" && integer.toString().length > 15) throw "integer should be entered as a string for precision"; 
    var padding = "";
    for (i = 0; i < 63; i++)
        padding += "0";
    
    return (padding + JSBigInt(integer).toString(16).toLowerCase()).slice(-64);
}

function d2s(integer)
{
    return swapEndian(d2h(integer));
}


function s2d(scalar)
{
    return JSBigInt.parse(swapEndian(scalar), 16).toString();
}

function scReduce32(hex)
{
    var input = hexToBin(hex);
    if (input.length !== 32) throw "scReduce32: Invalid hextobin(hex) input length";

    var mem = Module._malloc(32);
    Module.HEAPU8.set(input, mem);
    Module.ccall('sc_reduce32', 'void', ['number'], [mem]);
    var output = Module.HEAPU8.subarray(mem, mem + 32);
    Module._free(mem);
    return binToHex(output);
}

function hashToScalar(buf)
{
    var hash = cnFastHash(buf);
    var scalar = this.scReduce32(hash);
    return scalar;
}

function secKeyToPub(sec)
{
    if (sec.length !== 64) throw "sec_key_to_pub: Invalid sec length";
    
    return binToHex(nacl.ll.ge_scalarmult_base(hexToBin(sec)));
}

function derivationToScalar(derivation, output_index)
{
    var buf = "";
    if (derivation.length !== (STRUCT_SIZES.EC_POINT * 2)) throw "derivation_to_scalar: Invalid derivation length!";

    buf += derivation;
    var enc = encodeVarint(output_index);
    if (enc.length > 10 * 2) throw "output_index didn't fit in 64-bit varint";

    buf += enc;
    return hashToScalar(buf);
}

function generateKeyDerivation(pub, sec)
{
    if (pub.length !== 64 || sec.length !== 64) throw "generate_key_derivation: Invalid pub or sec keys lengths";
    
    var P = geScalarMult(pub, sec);
    return geScalarMult(P, d2s(8)); //mul8 to ensure group
}

function derivePublicKey(derivation, out_index, pub)
{
    if (derivation.length !== 64 || pub.length !== 64) throw "derivePublicKey: Invalid derivation or pub key input lengths!";
    
    var s = derivationToScalar(derivation, out_index);
    return binToHex(nacl.ll.ge_add(hexToBin(pub), hexToBin(secKeyToPub(s))));
}

function scSub(scalar1, scalar2)
{
    if (scalar1.length !== 64 || scalar2.length !== 64) throw "scSub: Invalid scalar1 or scalar2 input lengths!";
    
    var scalar1_m = Module._malloc(STRUCT_SIZES.EC_SCALAR);
    var scalar2_m = Module._malloc(STRUCT_SIZES.EC_SCALAR);
    Module.HEAPU8.set(hexToBin(scalar1), scalar1_m);
    Module.HEAPU8.set(hexToBin(scalar2), scalar2_m);
    var derived_m = Module._malloc(STRUCT_SIZES.EC_SCALAR);
    Module.ccall("sc_sub", "void", ["number", "number", "number"], [derived_m, scalar1_m, scalar2_m]);
    var res = Module.HEAPU8.subarray(derived_m, derived_m + STRUCT_SIZES.EC_SCALAR);
    Module._free(scalar1_m);
    Module._free(scalar2_m);
    Module._free(derived_m);

    return binToHex(res);
}

function decodeRctEcdh(ecdh, key)
{
    var first = hashToScalar(key);
    var second = hashToScalar(first);
    return {
        mask: scSub(ecdh.mask, first),
        amount: scSub(ecdh.amount, second)
    };
}

function geDoubleScalarmultBaseVartime(c, P, r) 
{
    if (c.length !== 64 || P.length !== 64 || r.length !== 64) throw "geDoubleScalarmultBaseVartime: Invalid c, P or r input lengths!";

    return binToHex(nacl.ll.ge_double_scalarmult_base_vartime(hexToBin(c), hexToBin(P), hexToBin(r)));
}

function commit(amount, mask)
{
    if (!validHex(mask) || mask.length !== 64 || !validHex(amount) || amount.length !== 64) throw "invalid amount or mask!";
    
    var C = geDoubleScalarmultBaseVartime(amount, H, mask);
    return C;
}

function decodeRct(rv, i, der)
{
    var key = derivationToScalar(der, i);
    var ecdh = decodeRctEcdh(rv.ecdhInfo[i], key);
    var Ctmp = commit(ecdh.amount, ecdh.mask);

    if (Ctmp !== rv.outPk[i]) throw "mismatched commitments!";
    
    ecdh.amount = s2d(ecdh.amount);
    return ecdh;
}