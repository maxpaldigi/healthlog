import 'package:flutter_test/flutter_test.dart';
import 'package:healthyme/main.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  testWidgets('login screen shows brand and disclaimer', (tester) async {
    await tester.pumpWidget(const HealthyMeApp());
    await tester.pump(); // start boot
    await tester.pump(); // finish loadToken

    expect(find.text('HealthyMe'), findsOneWidget);
    expect(find.textContaining('Not a medical device'), findsOneWidget);
  });
}
