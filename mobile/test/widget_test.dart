import 'package:flutter_test/flutter_test.dart';
import 'package:healthyme/main.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  testWidgets('login screen asks for email and explains tap-to-open', (tester) async {
    await tester.pumpWidget(const HealthyMeApp());
    await tester.pump();
    await tester.pump();

    expect(find.text('HealthyMe'), findsOneWidget);
    expect(find.textContaining('Not a medical device'), findsOneWidget);
    expect(find.text('Email me a login link'), findsOneWidget);
    expect(find.textContaining('tap the login link'), findsNothing);
    expect(
      find.textContaining('open the email on this phone'),
      findsOneWidget,
    );
  });
}
