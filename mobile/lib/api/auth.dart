import 'client.dart';

class AuthApi {
  AuthApi(this.client);

  final ApiClient client;

  Future<Map<String, dynamic>> requestMagicLink(String email) {
    return client.post('/auth/magic-link', body: {'email': email.trim()});
  }

  Future<Map<String, dynamic>> exchangeToken({
    required String token,
    String deviceName = 'flutter-android',
  }) async {
    final result = await client.post('/auth/token', body: {
      'token': token,
      'device_name': deviceName,
    });
    final apiToken = result['token']?.toString();
    if (apiToken == null || apiToken.isEmpty) {
      throw ApiException(
        statusCode: 422,
        message: 'No token returned.',
        body: result,
      );
    }
    await client.saveToken(apiToken);
    return result;
  }

  /// Accepts either a raw token or a full magic-link URL.
  Future<Map<String, dynamic>> exchangeFromUrl(String urlOrToken) {
    final value = urlOrToken.trim();
    final match = RegExp(r'/login/magic/([^?]+)').firstMatch(value);
    final token = match != null ? Uri.decodeComponent(match.group(1)!) : value;
    return exchangeToken(token: token);
  }

  Future<void> logout() async {
    try {
      await client.post('/auth/logout');
    } catch (_) {
      // Always clear local token.
    }
    await client.saveToken(null);
  }

  Future<Map<String, dynamic>> me() => client.get('/me');

  Future<void> grantHealthConsent() async {
    await client.post('/consent/health-data', body: {
      'health_data_consent': '1',
    });
  }
}
