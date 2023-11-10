require 'jwt'

key_file = 'AuthKey_VNXC3B5279.p8'
team_id = '3BVLJW9STH'
client_id = 'com.cresignzone.lobi'
key_id = 'VNXC3B5279'

ecdsa_key = OpenSSL::PKey::EC.new IO.read key_file

headers = {
'kid' => key_id
}

claims = {
    'iss' => team_id,
    'iat' => Time.now.to_i,
    'exp' => Time.now.to_i + 86400*180,
    'aud' => 'https://appleid.apple.com',
    'sub' => client_id,
}

token = JWT.encode claims, ecdsa_key, 'ES256', headers

puts token
