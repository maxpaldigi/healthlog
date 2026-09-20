import 'dart:async';
import 'dart:io' show Platform;

import 'package:app_links/app_links.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import 'api/auth.dart';
import 'api/client.dart';

const _brand = Color(0xFF2A3B8F);
const _bg = Color(0xFFF6F0E8);

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const HealthyMeApp());
}

class HealthyMeApp extends StatefulWidget {
  const HealthyMeApp({super.key});

  @override
  State<HealthyMeApp> createState() => _HealthyMeAppState();
}

class _HealthyMeAppState extends State<HealthyMeApp> {
  final api = ApiClient();
  late final auth = AuthApi(api);
  final _appLinks = AppLinks();
  StreamSubscription<Uri>? _linkSub;

  bool booting = true;
  bool loggedIn = false;
  String? linkError;

  @override
  void initState() {
    super.initState();
    _boot();
  }

  @override
  void dispose() {
    _linkSub?.cancel();
    super.dispose();
  }

  Future<void> _boot() async {
    await api.loadToken();

    final inTest = !kIsWeb && Platform.environment.containsKey('FLUTTER_TEST');
    if (!inTest) {
      _linkSub = _appLinks.uriLinkStream.listen(_onIncomingLink);

      try {
        final initial = await _appLinks
            .getInitialLink()
            .timeout(const Duration(milliseconds: 500), onTimeout: () => null);
        if (initial != null) {
          await _handleMagicLink(initial);
        }
      } catch (_) {
        // No launch link (normal cold start).
      }
    }

    if (!mounted) return;
    setState(() {
      loggedIn = api.isLoggedIn;
      booting = false;
    });
  }

  void _onIncomingLink(Uri uri) {
    unawaited(_handleMagicLink(uri));
  }

  Future<void> _handleMagicLink(Uri uri) async {
    if (!_looksLikeMagicLink(uri)) {
      return;
    }

    try {
      await auth.exchangeFromUrl(uri.toString());
      if (!mounted) return;
      setState(() {
        loggedIn = true;
        linkError = null;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => linkError = e.message);
    }
  }

  bool _looksLikeMagicLink(Uri uri) {
    final path = uri.path;
    return path.contains('/login/magic/') ||
        (uri.scheme == 'healthyme' && path.contains('/magic'));
  }

  void _onLoggedIn() => setState(() {
        loggedIn = true;
        linkError = null;
      });

  Future<void> _onLoggedOut() async {
    await auth.logout();
    setState(() => loggedIn = false);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'HealthyMe',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: _brand,
          brightness: Brightness.light,
          surface: _bg,
        ),
        scaffoldBackgroundColor: _bg,
        useMaterial3: true,
        filledButtonTheme: FilledButtonThemeData(
          style: FilledButton.styleFrom(
            minimumSize: const Size.fromHeight(56),
            textStyle: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
        ),
      ),
      home: booting
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : loggedIn
              ? HomeScreen(api: api, auth: auth, onLoggedOut: _onLoggedOut)
              : LoginScreen(
                  auth: auth,
                  onLoggedIn: _onLoggedIn,
                  linkError: linkError,
                ),
    );
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({
    super.key,
    required this.auth,
    required this.onLoggedIn,
    this.linkError,
  });

  final AuthApi auth;
  final VoidCallback onLoggedIn;
  final String? linkError;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final email = TextEditingController();
  String? message;
  bool busy = false;
  bool showPasteFallback = false;
  final link = TextEditingController();

  @override
  void dispose() {
    email.dispose();
    link.dispose();
    super.dispose();
  }

  Future<void> _requestLink() async {
    setState(() {
      busy = true;
      message = null;
    });
    try {
      final result = await widget.auth.requestMagicLink(email.text);
      setState(() {
        message =
            'Check your email and tap the login link. It opens this app and signs you in.';
        final loginUrl = result['login_url']?.toString();
        if (loginUrl != null) {
          // Local/dev mailers only — auto-open for convenience.
          link.text = loginUrl;
        }
      });
    } on ApiException catch (e) {
      setState(() => message = e.message);
    } finally {
      setState(() => busy = false);
    }
  }

  Future<void> _redeem() async {
    setState(() {
      busy = true;
      message = null;
    });
    try {
      await widget.auth.exchangeFromUrl(link.text);
      widget.onLoggedIn();
    } on ApiException catch (e) {
      setState(() => message = e.message);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 24),
            Text(
              'HealthyMe',
              style: Theme.of(context).textTheme.headlineLarge?.copyWith(
                    color: _brand,
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              'Blood pressure and sugar logbook. Not a medical device. Not medical advice.',
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                    color: Colors.black87,
                  ),
            ),
            const SizedBox(height: 32),
            TextField(
              controller: email,
              keyboardType: TextInputType.emailAddress,
              autocorrect: false,
              decoration: const InputDecoration(
                labelText: 'Email',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: busy ? null : _requestLink,
              child: Text(busy ? 'Please wait…' : 'Email me a login link'),
            ),
            if (widget.linkError != null) ...[
              const SizedBox(height: 16),
              Text(
                widget.linkError!,
                style: const TextStyle(color: Colors.red, fontSize: 16),
              ),
            ],
            if (message != null) ...[
              const SizedBox(height: 16),
              Text(message!, style: const TextStyle(fontSize: 16)),
            ],
            const SizedBox(height: 24),
            Text(
              'After you request a link: open the email on this phone and tap the button. HealthyMe should open and finish login.',
              style: Theme.of(context).textTheme.bodyLarge,
            ),
            const SizedBox(height: 24),
            TextButton(
              onPressed: () =>
                  setState(() => showPasteFallback = !showPasteFallback),
              child: Text(
                showPasteFallback
                    ? 'Hide paste option'
                    : 'Link did not open the app? Paste it here',
              ),
            ),
            if (showPasteFallback) ...[
              const SizedBox(height: 8),
              TextField(
                controller: link,
                maxLines: 3,
                decoration: const InputDecoration(
                  labelText: 'Login link',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              OutlinedButton(
                onPressed: busy ? null : _redeem,
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(56),
                ),
                child: const Text('Open HealthyMe'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({
    super.key,
    required this.api,
    required this.auth,
    required this.onLoggedOut,
  });

  final ApiClient api;
  final AuthApi auth;
  final Future<void> Function() onLoggedOut;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, dynamic>? home;
  String? error;
  bool loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final data = await widget.api.get('/home');
      setState(() {
        home = data;
        loading = false;
      });
    } on ApiException catch (e) {
      if (e.consentRequired) {
        await widget.auth.grantHealthConsent();
        return _load();
      }
      setState(() {
        error = e.message;
        loading = false;
      });
    } catch (e) {
      setState(() {
        error = e.toString();
        loading = false;
      });
    }
  }

  Future<void> _logBp() async {
    final profileName = home?['profile']?['name']?.toString() ?? 'you';
    final now = DateFormat("yyyy-MM-dd'T'HH:mm").format(DateTime.now());
    final systolic = TextEditingController(text: '120');
    final diastolic = TextEditingController(text: '80');

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Blood pressure for $profileName'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: systolic,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Systolic'),
            ),
            TextField(
              controller: diastolic,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Diastolic'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Save'),
          ),
        ],
      ),
    );

    if (ok != true) return;

    try {
      await widget.api.post('/bp', body: {
        'systolic': int.parse(systolic.text),
        'diastolic': int.parse(diastolic.text),
        'measured_at': now,
        'tag': 'none',
      });
      await _load();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final name = home?['profile']?['name']?.toString() ?? '';
    final slots = (home?['todaySlots'] as List?) ?? [];

    return Scaffold(
      appBar: AppBar(
        title: const Text('HealthyMe'),
        backgroundColor: _brand,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            onPressed: widget.onLoggedOut,
            icon: const Icon(Icons.logout),
            tooltip: 'Log out',
          ),
        ],
      ),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(error!, textAlign: TextAlign.center),
                        const SizedBox(height: 16),
                        FilledButton(
                            onPressed: _load, child: const Text('Try again')),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(24),
                    children: [
                      Text(
                        'Hello, $name',
                        style: Theme.of(context)
                            .textTheme
                            .headlineMedium
                            ?.copyWith(fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Photograph a meter, type a number, or add food — on the website for now. This app logs blood pressure.',
                      ),
                      const SizedBox(height: 24),
                      Text(
                        'Today’s blood pressure',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 12),
                      ...slots.map((slot) {
                        final map = slot as Map<String, dynamic>;
                        final filled = map['filled'] == true;
                        return Card(
                          child: ListTile(
                            title: Text(map['label']?.toString() ?? ''),
                            trailing: Icon(
                              filled
                                  ? Icons.check_circle
                                  : Icons.circle_outlined,
                              color: filled ? Colors.green : Colors.grey,
                              size: 32,
                            ),
                          ),
                        );
                      }),
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed: _logBp,
                        child: const Text('Add blood pressure'),
                      ),
                      const SizedBox(height: 24),
                      Text(
                        'Display only — never a diagnosis. Check with your clinic about your numbers.',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: Colors.black54,
                            ),
                      ),
                    ],
                  ),
                ),
    );
  }
}
