import 'package:academy_lms_app/widgets/appbar_one.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants.dart';

class VerificationScreen extends StatefulWidget {
  static const routeName = '/email_verification';
  const VerificationScreen({super.key});

  @override
  // ignore: library_private_types_in_public_api
  _VerificationScreenState createState() => _VerificationScreenState();
}

class _VerificationScreenState extends State<VerificationScreen> {
  GlobalKey<FormState> globalFormKey = GlobalKey<FormState>();
  final scaffoldKey = GlobalKey<ScaffoldState>();

  final _isLoading = false;

  final _boxController1 = TextEditingController();
  final _boxController2 = TextEditingController();
  final _boxController3 = TextEditingController();
  final _boxController4 = TextEditingController();
  final _boxController5 = TextEditingController();
  final _boxController6 = TextEditingController();

  final _boxFocus1 = FocusNode();
  final _boxFocus2 = FocusNode();
  final _boxFocus3 = FocusNode();
  final _boxFocus4 = FocusNode();
  final _boxFocus5 = FocusNode();
  final _boxFocus6 = FocusNode();

  late List<TextEditingController> _controllers;
  late List<FocusNode> _focus;
  late TextEditingController _selectedController;
  late FocusNode _selectedFocus;

  String _value = '';

  @override
  void initState() {
    super.initState();
    _controllers = [
      _boxController1,
      _boxController2,
      _boxController3,
      _boxController4,
      _boxController5,
      _boxController6,
    ];
    _focus = [
      _boxFocus1,
      _boxFocus2,
      _boxFocus3,
      _boxFocus4,
      _boxFocus5,
      _boxFocus6,
    ];
  }

  InputDecoration getInputDecoration(String hintext, IconData iconData) {
    return InputDecoration(
      enabledBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(12.0)),
        borderSide: BorderSide(color: Colors.white, width: 2),
      ),
      focusedBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(12.0)),
        borderSide: BorderSide(color: Colors.white, width: 2),
      ),
      border: const OutlineInputBorder(
        borderSide: BorderSide(color: Colors.white),
        borderRadius: BorderRadius.all(
          Radius.circular(12.0),
        ),
      ),
      focusedErrorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(12.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      errorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(12.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      filled: true,
      prefixIcon: Icon(
        iconData,
        color: kInputBoxBackGroundColor,
      ),
      hintStyle: const TextStyle(color: Colors.black54, fontSize: 14),
      hintText: hintext,
      fillColor: kBackGroundColor,
      contentPadding: const EdgeInsets.symmetric(vertical: 18, horizontal: 15),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBackGroundColor,
      appBar: const AppBarOne(),
      body: SingleChildScrollView(
        child: Column(
          children: [
            const SizedBox(
              height: 30,
            ),
            Center(
              child: Form(
                key: globalFormKey,
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(
                        height: 20,
                      ),
                      const Center(
                        child: Text(
                          "Verify It's You",
                          style: TextStyle(
                            fontSize: 28,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                      const SizedBox(
                        height: 40,
                      ),
                      const Text(
                        'We have sent the verification code to the email', 
                        style: TextStyle(
                          color: kGreyLightColor,
                          fontSize: 15, 
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const Text(
                        'creativeitem@gmail.com', 
                        style: TextStyle(
                          fontSize: 16, 
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const SizedBox(
                        height: 30,
                      ),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: List.generate(
                          6,
                          (index) => GestureDetector(
                            onTap: () {
                              // if nothing has been entered focus on the first box
                              if (_value.isEmpty) {
                                setState(() {
                                  _selectedFocus = _focus[0];
                                  _selectedController = _controllers[0];
                                });
                                FocusScope.of(context)
                                    .requestFocus(_selectedFocus);
                                // else focus on the box that was tapped
                              } else {
                                setState(() {
                                  _selectedFocus = _focus[index];
                                  _selectedController = _controllers[index];
                                });
                                FocusScope.of(context)
                                    .requestFocus(_selectedFocus);
                              }
                              // print(_selectedController.text);
                            },
                            child: Container(
                              alignment: Alignment.center,
                              padding: const EdgeInsets.symmetric(
                                vertical: 15,
                                horizontal: 15,
                              ),
                              decoration: BoxDecoration(
                                color: kInputBoxBackGroundColor,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: SizedBox(
                                width: 15,
                                height: 25,
                                child: TextField(
                                  controller: _controllers[index],
                                  focusNode: _focus[index],
                                  keyboardType: TextInputType.number,
                                  inputFormatters: [
                                    FilteringTextInputFormatter.allow(
                                        RegExp(r'^[0-9]+$')),
                                  ],
                                  onTap: () {
                                    // if nothing has been entered focus on the first box
                                    if (_value.isEmpty) {
                                      setState(() {
                                        _selectedFocus = _focus[0];
                                        _selectedController =
                                            _controllers[0];
                                      });
                                      FocusScope.of(context)
                                          .requestFocus(_selectedFocus);
                                    }
                                  },
                                  onChanged: (val) {
                                    if (val.isNotEmpty) {
                                      // if user enters value on a box that already has a value
                                      // the old value will be replaced by the new one
                                      if (val.length > 1) {
                                        // print('hi');
                                        _selectedController.clear();
                                        setState(() {
                                          _selectedController.text =
                                              val.split('').last;
                                        });
                                      }
                                      // if somethin was entered add all values together
                                      setState(() {
                                        _value = _controllers.fold<String>(
                                            '',
                                            (prevVal, element) =>
                                                prevVal + element.text);
                                      });
                                      // if user hasnt gotten to the last box the focus on the next box
                                      if (index + 1 < _focus.length) {
                                        _selectedFocus = _focus[index + 1];
                                        _selectedController =
                                            _controllers[index + 1];
                                        FocusScope.of(context)
                                            .requestFocus(_selectedFocus);
                                        // if user has gotten to last box close keyboard
                                      } else {
                                        FocusScope.of(context).unfocus();
                                        _selectedFocus = _focus[0];
                                        _selectedController =
                                            _controllers[0];
                                      }
                                    } // if val isEmpty (i.e number was deleted from the box) do nothing
                                    // print(_value);
                                  },
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 10),
                      Padding(
                        padding: const EdgeInsets.only(top: 10.0),
                        child: SizedBox(
                          width: double.infinity,
                          child: _isLoading
                            ? const Center(child: CircularProgressIndicator())
                            : Padding(
                                padding: const EdgeInsets.symmetric(vertical: 15.0),
                                child: MaterialButton(
                                  elevation: 0,
                                  color: kDefaultColor,
                                  onPressed: () {},
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 20, vertical: 16),
                                  shape: RoundedRectangleBorder(
                                    borderRadius:
                                        BorderRadiusDirectional.circular(16),
                                    // side: const BorderSide(color: kRedColor),
                                  ),
                                  child: const Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.center,
                                    children: [
                                      Text(
                                        'Verify',
                                        style: TextStyle(
                                          fontSize: 16,
                                          color: Colors.white,
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                        ),
                      ),
                      const SizedBox(height: 30),
                      const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 20.0),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              'Didn’t get the code?', 
                              style: TextStyle(
                                color: kGreyLightColor,
                                fontSize: 16, 
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            Text(
                              ' Other Option', 
                              style: TextStyle(
                                color: kSignUpTextColor,
                                fontSize: 16, 
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(
                        height: 60,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
