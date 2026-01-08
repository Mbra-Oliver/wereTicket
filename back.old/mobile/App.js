import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { StatusBar } from 'expo-status-bar';
import LoginScreen from './screens/LoginScreen';
import EventListScreen from './screens/EventListScreen';
import CheckInScreen from './screens/CheckInScreen';
import GuestListScreen from './screens/GuestListScreen';
import { AuthProvider } from './contexts/AuthContext';

const Stack = createStackNavigator();

export default function App() {
  return (
    <AuthProvider>
      <NavigationContainer>
        <StatusBar style="auto" />
        <Stack.Navigator initialRouteName="Login">
          <Stack.Screen 
            name="Login" 
            component={LoginScreen}
            options={{ headerShown: false }}
          />
          <Stack.Screen 
            name="EventList" 
            component={EventListScreen}
            options={{ title: 'Événements' }}
          />
          <Stack.Screen 
            name="CheckIn" 
            component={CheckInScreen}
            options={{ title: 'Check-in' }}
          />
          <Stack.Screen 
            name="GuestList" 
            component={GuestListScreen}
            options={{ title: 'Liste des invités' }}
          />
        </Stack.Navigator>
      </NavigationContainer>
    </AuthProvider>
  );
}

